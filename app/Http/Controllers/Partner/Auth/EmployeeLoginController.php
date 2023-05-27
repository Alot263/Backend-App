<?php

namespace App\Http\Controllers\Partner\Auth;

use App\Http\Controllers\Controller;
use App\Models\PartnerEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\VendorEmployee;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Session;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;

class EmployeeLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:partner', ['except' => 'logout']);
    }

    public function login()
    {
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());
        return view('partner-views.auth.login', compact('custome_recaptcha'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $recaptcha = Helpers::get_business_settings('recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response;
                        $response = Http::get($url);
                        $response = $response->json();
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('messages.ReCAPTCHA Failed'));
                        }
                    },
                ],
            ]);
        } else if(strtolower(session('six_captcha')) != strtolower($request->custome_recaptcha2))
        {
            Toastr::error(translate('messages.ReCAPTCHA Failed'));
            return back();
        }

        $employee = PartnerEmployee::where('email', $request->email)->first();
        if($employee)
        {
            if($employee->delivery_company->status == 0)
            {
                return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors([translate('messages.inactive_partner_warning')]);
            }
        }
        if (auth('partner_employee')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            $employee->is_logged_in = 1;
            $employee->save();
            return redirect()->route('partner.dashboard');
        }

        return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors([translate('messages.credentials_does_not_match')]);
    }

    public function logout(Request $request)
    {
        auth()->guard('partner_employee')->logout();
        return redirect()->route('partner.auth.login');
    }
}
