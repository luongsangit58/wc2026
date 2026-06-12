<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function privacy()
    {
        return view('pages.privacy', ['updated' => '12 June 2026']);
    }

    public function terms()
    {
        return view('pages.terms', ['updated' => '12 June 2026']);
    }
}
