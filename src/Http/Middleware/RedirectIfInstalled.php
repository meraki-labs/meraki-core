<?php

namespace Meraki\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Meraki\Core\Installer\State\MerakiState;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $state = MerakiState::load();

        if (!$state->installed && !$request->routeIs('meraki.install.*')) {
            return redirect()->route('meraki.install.welcome');
        }

        return $next($request);
    }
}
