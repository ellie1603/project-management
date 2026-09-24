<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Render a fragment-only view for AJAX-driven pagination/filtering
     * (see resources/js/app.js `ajaxRegions`), or the full page otherwise.
     *
     * @param  array<string, mixed>  $data
     */
    protected function respond(Request $request, string $view, string $fragmentView, array $data = []): View
    {
        return view($request->ajax() ? $fragmentView : $view, $data);
    }
}
