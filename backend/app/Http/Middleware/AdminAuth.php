<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';

        if (!$request->session()->has('admin_logged_in_' . $companyCode)) {
            // Use to() with a relative path to avoid APP_URL port mismatch
            // (e.g. when running on port 8001/8002, route() would redirect to APP_URL port 8000)
            return redirect()->to('/admin/login?company=' . $companyCode);
        }

        return $next($request);
    }
}
