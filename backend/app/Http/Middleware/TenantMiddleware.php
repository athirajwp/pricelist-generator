<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip switching for the super admin system panel
        if ($request->is('admin_sys*') || $request->is('api/admin_sys*')) {
            return $next($request);
        }

        try {
            $defaultConn = config('database.default');

            // 1. Identify the active company
            $httpHost = $request->getHttpHost();
            
            $currentCompany = null;
            
            // 1. Query parameters override (highest priority)
            if ($request->has('company')) {
                $currentCompany = \App\Models\Company::where('code', $request->query('company'))->first();
            } elseif ($request->has('company_id')) {
                $currentCompany = \App\Models\Company::find($request->query('company_id'));
            }

            // 1.5. Match by Port Number (e.g. 7001 = 1st company, 7002 = 2nd company, etc.)
            if (!$currentCompany) {
                $hostParts = explode(':', $httpHost);
                if (count($hostParts) > 1 && is_numeric($hostParts[1])) {
                    $port = (int)$hostParts[1];
                    if ($port >= 7001 && $port <= 7999) {
                        $companyIndex = $port - 7001;
                        $currentCompany = \App\Models\Company::orderBy('id', 'asc')->offset($companyIndex)->first();
                    }
                }
            }
            
            // 2. Domain/port matching
            if (!$currentCompany) {
                // Try matching full HTTP host (including port)
                $currentCompany = \App\Models\Company::where('website', $httpHost)
                    ->orWhere('website', 'like', '%' . $httpHost . '%')
                    ->first();
                
                // Interchangeable check: if accessed via 127.0.0.1, also check with 'localhost'
                if (!$currentCompany && str_starts_with($httpHost, '127.0.0.1')) {
                    $localhostAlternative = str_replace('127.0.0.1', 'localhost', $httpHost);
                    $currentCompany = \App\Models\Company::where('website', $localhostAlternative)
                        ->orWhere('website', 'like', '%' . $localhostAlternative . '%')
                        ->first();
                }
                
                // Interchangeable check: if accessed via localhost, also check with '127.0.0.1'
                if (!$currentCompany && str_starts_with($httpHost, 'localhost')) {
                    $ipAlternative = str_replace('localhost', '127.0.0.1', $httpHost);
                    $currentCompany = \App\Models\Company::where('website', $ipAlternative)
                        ->orWhere('website', 'like', '%' . $ipAlternative . '%')
                        ->first();
                }
                
                // Fallback to matching hostname only (without port)
                if (!$currentCompany) {
                    $hostOnly = explode(':', $httpHost)[0];
                    $currentCompany = \App\Models\Company::where('website', $hostOnly)
                        ->orWhere('website', 'like', '%' . $hostOnly . '%')
                        ->first();
                    
                    if (!$currentCompany && ($hostOnly === '127.0.0.1' || $hostOnly === 'localhost')) {
                        $altHost = ($hostOnly === '127.0.0.1') ? 'localhost' : '127.0.0.1';
                        $currentCompany = \App\Models\Company::where('website', $altHost)
                            ->orWhere('website', 'like', '%' . $altHost . '%')
                            ->first();
                    }
                }
            }
            
            // 3. Fallback to active company in session if host did not match any tenant website
            if (!$currentCompany && $request->hasSession() && session()->has('active_company_id')) {
                $currentCompany = \App\Models\Company::find(session('active_company_id'));
            }
            
            // 4. Default fallback to first company if still no resolution
            if (!$currentCompany) {
                $currentCompany = \App\Models\Company::orderBy('id', 'asc')->first();
            }

            // 5. Store resolved company ID in session
            if ($currentCompany && $request->hasSession()) {
                session(['active_company_id' => $currentCompany->id]);
            }

            // Check if company is deactivated / inactive
            if ($currentCompany && $currentCompany->status === 'inactive') {
                $storeName = $currentCompany->name;
                $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Inactive | {$storeName}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-800/80 backdrop-blur-xl border border-slate-700 p-8 rounded-3xl text-center space-y-6 shadow-2xl">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-rose-500/10 border border-rose-500/35 rounded-2xl text-rose-500 text-3xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="space-y-2">
            <h1 class="text-2xl font-black tracking-tight text-white">Website Inactive</h1>
            <p class="text-sm text-slate-400">The website for <strong>{$storeName}</strong> is currently deactivated or suspended by the administrator.</p>
        </div>
        <div class="border-t border-slate-700/60 pt-6 text-[11px] text-slate-500 uppercase tracking-widest font-semibold">
            Please Contact System Administrator
        </div>
    </div>
</body>
</html>
HTML;
                return response($html, 403);
            }

            // 2. Switch database connection if company found
            if ($currentCompany) {
                $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $currentCompany->code));
                
                // Check if the tenant database exists on the server
                $dbExists = false;
                try {
                    $connName = config('database.connections.central') ? 'central' : $defaultConn;
                    $driver = \Illuminate\Support\Facades\DB::connection($connName)->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
                    if ($driver === 'pgsql') {
                        $existsQuery = \Illuminate\Support\Facades\DB::connection($connName)
                            ->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
                    } elseif ($driver === 'mysql') {
                        $existsQuery = \Illuminate\Support\Facades\DB::connection($connName)
                            ->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$tenantDb]);
                    }
                    $dbExists = !empty($existsQuery);
                } catch (\Exception $existsEx) {
                    // Fail-safe default
                }

                // Self-healing: if the tenant DB doesn't exist, create, migrate and seed it dynamically
                if (!$dbExists) {
                    try {
                        \Illuminate\Support\Facades\DB::connection('central')->statement("CREATE DATABASE $tenantDb");
                        
                        $config = config("database.connections.central");
                        $config['database'] = $tenantDb;
                        config(["database.connections.tenant_migration" => $config]);
                        \Illuminate\Support\Facades\DB::purge('tenant_migration');
                        
                        \Illuminate\Support\Facades\Artisan::call('migrate', [
                            '--database' => 'tenant_migration',
                            '--force' => true,
                        ]);
                        
                        \Illuminate\Support\Facades\Artisan::call('db:seed', [
                            '--database' => 'tenant_migration',
                            '--class' => 'Database\\Seeders\\CategoryAndProductSeeder',
                            '--force' => true,
                        ]);
                        
                        $dbExists = true;
                    } catch (\Exception $setupEx) {
                        \Illuminate\Support\Facades\Log::error("Self-healing tenant database setup failed for $tenantDb: " . $setupEx->getMessage());
                    }
                }

                if ($dbExists) {
                    config(["database.connections.{$defaultConn}.database" => $tenantDb]);
                    
                    \Illuminate\Support\Facades\DB::purge($defaultConn);
                    \Illuminate\Support\Facades\DB::reconnect($defaultConn);
                }
            }

            // Share currentCompany with all views & set dynamic SMTP config if available
            if ($currentCompany) {
                view()->share('currentCompany', $currentCompany);

                if (!empty($currentCompany->smtp_host) && !empty($currentCompany->smtp_user) && !empty($currentCompany->smtp_pass)) {
                    $sslVal = strtolower((string)$currentCompany->smtp_ssl);
                    $encryption = ($sslVal === 'true' || $sslVal === 'ssl' || $currentCompany->smtp_port == 465) ? 'ssl' : 'tls';
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => trim($currentCompany->smtp_host),
                        'mail.mailers.smtp.port' => (int) ($currentCompany->smtp_port ?: 587),
                        'mail.mailers.smtp.encryption' => $encryption,
                        'mail.mailers.smtp.username' => trim($currentCompany->smtp_user),
                        'mail.mailers.smtp.password' => trim(str_replace(' ', '', $currentCompany->smtp_pass)),
                        'mail.from.address' => trim($currentCompany->smtp_user),
                        'mail.from.name' => $currentCompany->name ?: config('mail.from.name'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Tenant connection switching failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
