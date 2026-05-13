<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSumberDanaSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if ($request->is('*/pilih-sumber-dana') || ! $request->isMethod('GET')) {
            return $next($request);
        }

        if (! session()->has('sumber_dana')) {
            return redirect('/admin/pilih-sumber-dana');
        }

        return $next($request);
    }
}
