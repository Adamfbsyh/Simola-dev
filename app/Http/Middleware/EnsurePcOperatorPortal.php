<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePcOperatorPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('pc_operator')) {
            return $next($request);
        }

        if (
            $request->routeIs('operator-chat.portal*')
            || $request->routeIs('operator-chat.notes.*')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun PC Operator hanya dapat mengakses Chat dan Catatan Operator.',
            ], 403);
        }

        return redirect()->route('operator-chat.portal');
    }
}
