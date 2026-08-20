<?php

namespace App\Http\Controllers;

use App\Support\TerminatesWebSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SessionCloseController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->user()) {
            TerminatesWebSession::logout($request, 'auth.logout_tab_closed');
        }

        return response()->noContent();
    }
}
