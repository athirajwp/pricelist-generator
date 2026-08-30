<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminSysApiController extends Controller
{
    /**
     * Check super admin authentication status.
     */
    public function checkAuth(Request $request)
    {
        $authenticated = session()->has('super_admin_logged_in') && session('super_admin_logged_in') === true;
        return response()->json([
            'authenticated' => $authenticated,
            'username' => Setting::get('super_admin_username', env('SUPER_ADMIN_USERNAME', 'superadmin')),
        ]);
    }

    /**
     * Handle super admin login authentication.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expectedUser = Setting::get('super_admin_username');
        if (!$expectedUser) {
            $expectedUser = env('SUPER_ADMIN_USERNAME', 'superadmin');
        }

        $expectedPass = Setting::get('super_admin_password');
        if (!$expectedPass) {
            $expectedPass = env('SUPER_ADMIN_PASSWORD', 'superadmin123');
        }

        $isPasswordMatch = false;
        if (str_starts_with($expectedPass, '$2y$') || str_starts_with($expectedPass, '$2a$')) {
            $isPasswordMatch = Hash::check($request->password, $expectedPass);
        } else {
            $isPasswordMatch = ($request->password === $expectedPass);
        }

        if ($request->username === $expectedUser && $isPasswordMatch) {
            session(['super_admin_logged_in' => true]);
            return response()->json([
                'success' => true,
                'message' => 'Super Admin authentication successful!',
                'redirect' => '/admin_sys/company'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid username or password!'
        ], 422);
    }

    /**
     * Log out super admin.
     */
    public function logout(Request $request)
    {
        session()->forget('super_admin_logged_in');
        return response()->json([
            'success' => true,
            'message' => 'Super Admin logged out successfully.'
        ]);
    }

    /**
     * Fetch list of companies for super admin dashboard.
     */
    public function companies()
    {
        $this->ensureCompanyTableExists();

        // Auto-seed the current shop as the default company if none exist
        if (Company::count() === 0) {
            try {
                Company::create([
                    'code'       => preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(Setting::get('store_name', env('APP_NAME', 'shop')))),
                    'name'       => Setting::get('store_name', env('APP_NAME', 'My Shop')),
                    'website'    => env('APP_URL', 'localhost:8000'),
                    'contact_1'  => Setting::get('store_phone', env('STORE_PHONE', '')),
                    'contact_2'  => Setting::get('store_whatsapp', env('STORE_WHATSAPP', '')),
                    'address'    => Setting::get('store_address', env('STORE_ADDRESS', '')),
                    'gst_no'     => Setting::get('store_gst', ''),
                    'pan_no'     => Setting::get('store_pan', ''),
                    'status'     => 'active',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Auto-seed default company failed: ' . $e->getMessage());
            }
        }

        $companies = Company::orderBy('id', 'asc')->get();
        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * Store a newly created company domain and create tenant database.
     */
    public function storeCompany(Request $request)
    {
        Log::info('SuperAdmin API Company store request received:', $request->all());

        $request->validate([
            'code' => 'required|string|max:255|unique:companies,code',
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'contact_1' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $data = $request->all();
        $data['website'] = !empty($data['website']) ? $data['website'] : strtolower($data['code']) . '.com';
        $data['contact_1'] = $data['contact_1'] ?? '';
        $data['status'] = $data['status'] ?? 'active';

        // Automatically assign port if website is localhost / 127.0.0.1
        $websiteClean = strtolower(trim($data['website']));
        if ($websiteClean === 'localhost' || $websiteClean === '127.0.0.1' || str_starts_with($websiteClean, 'localhost:') || str_starts_with($websiteClean, '127.0.0.1:')) {
            $hostOnly = explode(':', $websiteClean)[0];
            $nextPort = 7001 + Company::count();
            $data['website'] = $hostOnly . ':' . $nextPort;
        }

        // Handle dynamic file uploads
        $companyCode = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->code));
        $files = ['bank_qr_1', 'bank_qr_2', 'bank_qr_3', 'logo_path', 'favicon_path'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $request->validate([
                    $file => 'image|mimes:jpeg,png,jpg,webp,gif|max:3072'
                ]);
                
                $fileName = time() . '_' . $file . '_' . uniqid() . '.' . $request->file($file)->extension();
                $request->file($file)->move(public_path("uploads/companies/{$companyCode}/profile"), $fileName);
                $data[$file] = "uploads/companies/{$companyCode}/profile/" . $fileName;
            }
        }

        // Create company record
        $company = Company::create($data);

        // Dynamically create, migrate, and seed database if privileges permit
        try {
            $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
            $dbCreated = false;
            
            try {
                $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'pgsql') {
                    $exists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
                } else {
                    $exists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$tenantDb]);
                }
                if (empty($exists)) {
                    DB::connection('central')->statement("CREATE DATABASE $tenantDb");
                }
                $dbCreated = true;
            } catch (\Throwable $createDbEx) {
                Log::warning("CREATE DATABASE restricted on host for {$tenantDb}: " . $createDbEx->getMessage());
                $dbCreated = false;
            }

            if ($dbCreated) {
                try {
                    $config = config("database.connections.central") ?: config("database.connections." . config('database.default'));
                    if ($config) {
                        $config['database'] = $tenantDb;
                        config(["database.connections.tenant_migration" => $config]);

                        DB::purge('tenant_migration');

                        \Illuminate\Support\Facades\Artisan::call('migrate', [
                            '--database' => 'tenant_migration',
                            '--force' => true,
                        ]);

                        \Illuminate\Support\Facades\Artisan::call('db:seed', [
                            '--database' => 'tenant_migration',
                            '--class' => 'Database\\Seeders\\CategoryAndProductSeeder',
                            '--force' => true,
                        ]);
                    }
                } catch (\Throwable $migEx) {
                    Log::error('Tenant migration error: ' . $migEx->getMessage());
                }
            }
            
            $this->syncCompanyToSettings($company);
            
        } catch (\Throwable $e) {
            Log::error('Company setup warning: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'New company domain registered successfully!',
            'company' => $company
        ]);
    }

    /**
     * Update existing company domain record.
     */
    public function updateCompany(Request $request, $id)
    {
        Log::info("Company update request received for ID $id:", $request->all());

        $company = Company::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:255|unique:companies,code,' . $id,
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'contact_1' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $data = $request->only([
            'code', 'name', 'website', 'contact_1', 'contact_2', 'contact_3',
            'address', 'address_1', 'gst_no', 'gst_number', 'pan_no', 'pan_number',
            'msme_no', 'msme_number', 'status',
            'bank_name_1', 'bank_acc_1', 'bank_ifsc_1', 'bank_branch_1',
            'bank_name_2', 'bank_acc_2', 'bank_ifsc_2', 'bank_branch_2',
            'bank_name_3', 'bank_acc_3', 'bank_ifsc_3', 'bank_branch_3',
            'upi_id_1', 'upi_id_2', 'upi_id_3'
        ]);

        // Map form field aliases to model column names
        if (isset($data['gst_no']) && !isset($data['gst_number'])) {
            $data['gst_number'] = $data['gst_no'];
        }
        if (isset($data['pan_no']) && !isset($data['pan_number'])) {
            $data['pan_number'] = $data['pan_no'];
        }
        if (isset($data['msme_no']) && !isset($data['msme_number'])) {
            $data['msme_number'] = $data['msme_no'];
        }
        if (isset($data['address']) && !isset($data['address_1'])) {
            $data['address_1'] = $data['address'];
        }

        if (isset($data['website'])) {
            $websiteClean = strtolower(trim($data['website']));
            if (($websiteClean === 'localhost' || $websiteClean === '127.0.0.1' || str_starts_with($websiteClean, 'localhost:') || str_starts_with($websiteClean, '127.0.0.1:')) && !str_contains($data['website'], ':')) {
                $hostOnly = explode(':', $websiteClean)[0];
                $data['website'] = $hostOnly . ':' . (7000 + $company->id);
            }
        }

        $companyCode = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->code));
        $files = ['bank_qr_1', 'bank_qr_2', 'bank_qr_3', 'logo_path', 'favicon_path'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $request->validate([
                    $file => 'image|mimes:jpeg,png,jpg,webp,gif|max:3072'
                ]);
                
                $fileName = time() . '_' . $file . '_' . uniqid() . '.' . $request->file($file)->extension();
                $request->file($file)->move(public_path("uploads/companies/{$companyCode}/profile"), $fileName);
                $data[$file] = "uploads/companies/{$companyCode}/profile/" . $fileName;
            }
        }

        $company->update($data);
        $this->syncCompanyToSettings($company);

        return response()->json([
            'success' => true,
            'message' => 'Company domain record updated successfully!',
            'company' => $company
        ]);
    }

    /**
     * Sync Company fields (Name, Contacts, Address, Bank details) to Tenant DB Settings table.
     */
    private function syncCompanyToSettings(Company $company)
    {
        try {
            $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
            
            $address = $company->address_1 ?? ($company->address ?? '');
            if ($company->address_2) $address .= ', ' . $company->address_2;
            if ($company->city) $address .= ', ' . $company->city;
            if ($company->state) $address .= ', ' . $company->state;
            if ($company->pincode) $address .= ' - ' . $company->pincode;

            $settingsMap = [
                'store_name' => $company->name,
                'store_phone' => $company->contact_1,
                'store_phone_2' => $company->contact_2,
                'store_phone_3' => $company->contact_3,
                'store_phone_4' => $company->contact_4,
                'store_whatsapp' => $company->contact_2 ?: $company->contact_1,
                'store_email' => $company->email_1 ?: $company->email,
                'store_address' => $address,
                'bank_name' => $company->bank_name_1,
                'bank_acc_no' => $company->bank_acc_1,
                'bank_ifsc' => $company->bank_ifsc_1,
                'bank_holder' => $company->bank_holder_1,
                'store_upi' => $company->upi_id_1 ?? '',
            ];

            if ($company->bank_qr_1) {
                $settingsMap['store_upi_qr'] = $company->bank_qr_1;
            }

            $syncedToTenant = false;
            try {
                $config = config("database.connections.central") ?: config("database.connections." . config('database.default'));
                if ($config) {
                    $config['database'] = $tenantDb;
                    config(["database.connections.tenant_sync" => $config]);
                    DB::purge('tenant_sync');

                    $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
                    if ($driver === 'pgsql') {
                        $exists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
                    } else {
                        $exists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$tenantDb]);
                    }

                    if (!empty($exists)) {
                        foreach ($settingsMap as $key => $value) {
                            if ($value !== null) {
                                DB::connection('tenant_sync')->table('settings')->updateOrInsert(
                                    ['key' => $key],
                                    ['value' => $value, 'type' => 'text', 'updated_at' => now(), 'created_at' => now()]
                                );
                            }
                        }
                        $syncedToTenant = true;
                    }
                }
            } catch (\Throwable $tenantEx) {
                $syncedToTenant = false;
            }

            if (!$syncedToTenant) {
                foreach ($settingsMap as $key => $value) {
                    if ($value !== null) {
                        DB::table('settings')->updateOrInsert(
                            ['key' => $key],
                            ['value' => $value, 'type' => 'text', 'updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Tenant settings sync error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle company active/inactive status.
     */
    public function toggleCompanyStatus($id)
    {
        $company = Company::findOrFail($id);
        $company->status = ($company->status === 'active') ? 'inactive' : 'active';
        $company->save();

        return response()->json([
            'success' => true,
            'message' => "Company status toggled to {$company->status}.",
            'company' => $company
        ]);
    }

    /**
     * Delete company domain record.
     */
    public function destroyCompany($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company domain record removed successfully.'
        ]);
    }

    /**
     * Fetch Super Admin profile details.
     */
    public function profile()
    {
        $username = Setting::get('super_admin_username', env('SUPER_ADMIN_USERNAME', 'superadmin'));
        return response()->json([
            'success' => true,
            'username' => $username
        ]);
    }

    /**
     * Update Super Admin username and password.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'current_password' => 'required|string',
            'password' => 'nullable|string|min:6',
        ]);

        $currentActivePassword = Setting::get('super_admin_password');
        if (!$currentActivePassword) {
            $currentActivePassword = env('SUPER_ADMIN_PASSWORD', 'superadmin123');
        }

        $isMatch = false;
        if (str_starts_with($currentActivePassword, '$2y$') || str_starts_with($currentActivePassword, '$2a$')) {
            $isMatch = Hash::check($request->current_password, $currentActivePassword);
        } else {
            $isMatch = ($request->current_password === $currentActivePassword);
        }

        if (!$isMatch) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.'
            ], 422);
        }

        Setting::set('super_admin_username', $request->username, 'text');

        if ($request->filled('password')) {
            Setting::set('super_admin_password', Hash::make($request->password), 'text');
        }

        return response()->json([
            'success' => true,
            'message' => 'Super Admin profile details updated successfully!'
        ]);
    }

    /**
     * Ensure companies table exists.
     */
    protected function ensureCompanyTableExists()
    {
        if (!Schema::connection('central')->hasTable('companies')) {
            Schema::connection('central')->create('companies', function ($table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('website');
                $table->string('contact_1');
                $table->string('contact_2')->nullable();
                $table->string('contact_3')->nullable();
                $table->text('address')->nullable();
                $table->string('gst_no')->nullable();
                $table->string('pan_no')->nullable();
                $table->string('msme_no')->nullable();
                $table->string('bank_name_1')->nullable();
                $table->string('bank_acc_1')->nullable();
                $table->string('bank_ifsc_1')->nullable();
                $table->string('bank_branch_1')->nullable();
                $table->string('bank_qr_1')->nullable();
                $table->string('bank_name_2')->nullable();
                $table->string('bank_acc_2')->nullable();
                $table->string('bank_ifsc_2')->nullable();
                $table->string('bank_branch_2')->nullable();
                $table->string('bank_qr_2')->nullable();
                $table->string('bank_name_3')->nullable();
                $table->string('bank_acc_3')->nullable();
                $table->string('bank_ifsc_3')->nullable();
                $table->string('bank_branch_3')->nullable();
                $table->string('bank_qr_3')->nullable();
                $table->string('upi_id_1')->nullable();
                $table->string('upi_id_2')->nullable();
                $table->string('upi_id_3')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }
    }
    /**
     * Reset admin password for a specific company (tenant).
     * Super admin can set a new password without knowing the current one.
     */
    public function resetCompanyAdminPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $company = Company::findOrFail($id);
        $hashedPassword = Hash::make($request->password);

        $updatedInTenant = false;

        try {
            $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));

            $config = config("database.connections.central") ?: config("database.connections." . config('database.default'));
            if ($config) {
                $config['database'] = $tenantDb;
                config(["database.connections.tenant_pwd_reset" => $config]);
                DB::purge('tenant_pwd_reset');

                // Check if tenant database exists
                $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'pgsql') {
                    $exists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
                } else {
                    $exists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$tenantDb]);
                }

                if (!empty($exists)) {
                    DB::connection('tenant_pwd_reset')->table('settings')->updateOrInsert(
                        ['key' => 'admin_password'],
                        ['value' => $hashedPassword, 'type' => 'text', 'updated_at' => now(), 'created_at' => now()]
                    );
                    $updatedInTenant = true;
                }
            }
        } catch (\Throwable $tenantEx) {
            Log::warning('Tenant admin password reset fallback: ' . $tenantEx->getMessage());
            $updatedInTenant = false;
        }

        // Fallback: update in the local/default settings table
        if (!$updatedInTenant) {
            Setting::set('admin_password', $hashedPassword, 'text');
        }

        return response()->json([
            'success' => true,
            'message' => "Admin password for \"{$company->name}\" has been reset successfully.",
        ]);
    }
}
