<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

abstract class Controller
{
    /**
     * Render the Phase 4 Blade view once present, or return a minimal Phase 3 fallback
     * so route wiring, redirects, and controller payloads are testable without premature UI.
     */
    protected function renderPortalView(string $view, array $data = [], int $status = 200): View|Response
    {
        if (view()->exists($view)) {
            return response()->view($view, $data, $status);
        }

        return response("Pending Phase 4 view [{$view}]", $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-SpaceWorq-View' => $view,
        ]);
    }
}
