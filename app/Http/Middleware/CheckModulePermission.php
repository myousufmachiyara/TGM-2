<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModulePermission
{
    // Maps controller method names to permission action suffixes
    private const ACTION_MAP = [
        'store'   => 'create',
        'update'  => 'edit',
        'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Superadmin bypasses all permission checks
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // If permission already contains a dot it's fully qualified (e.g. reports.inventory)
        if (str_contains($permission, '.')) {
            $finalPermission = $permission;
        } else {
            // Build from module + mapped action (e.g. products + store → products.create)
            $action          = $request->route()->getActionMethod();
            $mappedAction    = self::ACTION_MAP[$action] ?? $action;
            $finalPermission = "{$permission}.{$mappedAction}";
        }

        if (!$user->can($finalPermission)) {
            return redirect()->route('unauthorized');
        }

        return $next($request);
    }
}