<?php

namespace App\Http\Controllers\Partner;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    public function lang($local)
    {
        $direction = BusinessSetting::where('key', 'site_direction')->first();
        $direction = $direction->value ?? 'ltr';
        $language = BusinessSetting::where('key', 'system_language')->first();
        foreach (json_decode($language['value'], true) as $key => $data) {
            if ($data['code'] == $local) {
                $direction = isset($data['direction']) ? $data['direction'] : 'ltr';
            }
        }
        session()->forget('partner_language_settings');
        Helpers::partner_language_load();
        session()->put('partner_site_direction', $direction);
        session()->put('partner_local', $local);
        return redirect()->back();
    }
}
