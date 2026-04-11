<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class LanguageController extends Controller
{
    public function switch($language)
    {
        app()->setLocale($language);


        session()->put('locale', $language);

        setlocale(LC_TIME, $language);


        Carbon::setLocale($language);


        //flash()->success(__('Language changed to').' '.strtoupper($language))->important();

    $dir = function_exists('language_direction') ? language_direction($language) : (strtolower(substr($language,0,2)) === 'ar' ? 'rtl' : 'ltr');
    session()->put('locale_dir', $dir);
    return redirect()->back();
    }
}
