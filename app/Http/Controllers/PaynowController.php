<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use Paynow\Payments\Paynow;

class PaynowController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paynow', 'payment_config');
        $paynow = false;
        if (!is_null($config) && $config->mode == 'live') {
            $paynow = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $paynow = json_decode($config->test_values);
        }

        if ($paynow) {
            $config = array(
                'integration_id'  => $paynow->integration_id,
                'integration_key' => $paynow->integration_key,
                'result_url'      => $paynow->result_url,
                'return_url'      => $paynow->return_url,
            );
            Config::set('paynow', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($data['payer_information']);

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "my_business";
            $business_logo = url('/');
        }

        return view('payment-views.paynow', compact('data', 'payer', 'business_logo', 'business_name'));
    }

    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $input = $request->all();
        $api = new Paynow(
            "INTEGRATION_ID",
            "INTEGRATION_KEY",
            "https://example.com/gateways/paynow/update",
            "https://example.com/return?gateway=paynow"
        );

        $payment = $api->createPayment($input['paynow_payment_id'], '');
        if (count($input) && !empty($input['paynow_payment_id'])) {
            $response = $api->payment->fetch($input['paynow_payment_id'])->capture(array('amount' => $payment['amount']));

            $this->payment::where(['id' => $request['payment_id']])->update([
                'payment_method' => 'paynow',
                'is_paid' => 1,
                'transaction_id' => $input['paynow_payment_id'],
            ]);
            $data = $this->payment::where(['id' => $request['paynow_payment_id']])->first();
            if (isset($data) && function_exists($data->success_hook)) {
                call_user_func($data->success_hook, $data);
            }
            return $this->payment_response($data, 'success');
        }
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
}
