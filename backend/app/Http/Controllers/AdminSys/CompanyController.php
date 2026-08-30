<?php

namespace App\Http\Controllers\AdminSys;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Display the website overview / company list.
     */
    public function index()
    {
        // Self-healing database initialization with new schema
        $this->ensureTableExists();

        $companies = Company::orderBy('id', 'asc')->get();

        return view('admin_sys.company', compact('companies'));
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Company store request received:', $request->all());

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

        // Handle standard dynamic file uploads
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

        // Create the company record in central DB first
        $company = Company::create($data);

        // Dynamically create, migrate, and seed the new database if permitted
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
            } catch (\Throwable $dbEx) {
                \Illuminate\Support\Facades\Log::warning("CREATE DATABASE restricted on host for {$tenantDb}: " . $dbEx->getMessage());
                $dbCreated = false;
            }

            if ($dbCreated) {
                try {
                    $config = config("database.connections.central");
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
                    \Illuminate\Support\Facades\Log::error('Tenant migration error: ' . $migEx->getMessage());
                }
            }
            
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tenant database setup warning: ' . $e->getMessage());
        }

        return redirect()->route('admin_sys.company.index')->with('success', 'New domain company registered successfully!');
    }


    /**
     * Update an existing company domain record.
     */
    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info("Company update request received for ID $id:", $request->all());

        $company = Company::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:255|unique:companies,code,' . $id,
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'contact_1' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $data = $request->all();

        // Automatically assign port if website is localhost / 127.0.0.1
        $websiteClean = strtolower(trim($data['website']));
        if ($websiteClean === 'localhost' || $websiteClean === '127.0.0.1' || str_starts_with($websiteClean, 'localhost:') || str_starts_with($websiteClean, '127.0.0.1:')) {
            $hostOnly = explode(':', $websiteClean)[0];
            $allCompanies = Company::orderBy('id', 'asc')->get();
            $compIndex = 0;
            foreach ($allCompanies as $idx => $comp) {
                if ($comp->id == $company->id) {
                    $compIndex = $idx;
                    break;
                }
            }
            $port = 7001 + $compIndex;
            $data['website'] = $hostOnly . ':' . $port;
        }

        // Handle standard dynamic file uploads
        $companyCode = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->code));
        $files = ['bank_qr_1', 'bank_qr_2', 'bank_qr_3', 'logo_path', 'favicon_path'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $request->validate([
                    $file => 'image|mimes:jpeg,png,jpg,webp,gif|max:3072'
                ]);

                // Delete old file if exists
                $oldPath = $company->{$file};
                if ($oldPath && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }
                
                $fileName = time() . '_' . $file . '_' . uniqid() . '.' . $request->file($file)->extension();
                $request->file($file)->move(public_path("uploads/companies/{$companyCode}/profile"), $fileName);
                $data[$file] = "uploads/companies/{$companyCode}/profile/" . $fileName;
            }
        }

        $oldCode = $company->code;
        $newCode = $request->code;
        $codeChanged = (strtolower($oldCode) !== strtolower($newCode));

        if ($codeChanged) {
            $oldDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $oldCode));
            $newDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $newCode));

            // Check if new database already exists
            $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $newExists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$newDb]);
            } else {
                $newExists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$newDb]);
            }
            if (!empty($newExists)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['code' => "Database $newDb already exists. Cannot rename to this code."]);
            }

            // Terminate active connections to old database if pgsql so we can rename it
            if ($driver === 'pgsql') {
                try {
                    DB::connection('central')->statement("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?", [$oldDb]);
                } catch (\Exception $e) {
                    // Ignore connection termination errors
                }
            }

            // Rename database
            try {
                if ($driver === 'pgsql') {
                    $oldExists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$oldDb]);
                    if (!empty($oldExists)) {
                        DB::connection('central')->statement("ALTER DATABASE $oldDb RENAME TO $newDb");
                    }
                } else {
                    $oldExists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$oldDb]);
                    if (!empty($oldExists)) {
                        DB::connection('central')->statement("CREATE DATABASE $newDb");
                        $tables = DB::connection('central')->select("SHOW TABLES FROM $oldDb");
                        foreach ($tables as $table) {
                            $tableName = current((array)$table);
                            DB::connection('central')->statement("RENAME TABLE $oldDb.$tableName TO $newDb.$tableName");
                        }
                        DB::connection('central')->statement("DROP DATABASE $oldDb");
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to rename database from $oldDb to $newDb: " . $e->getMessage());
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['code' => "Failed to rename database: " . $e->getMessage()]);
            }

            // Also rename the upload folder if it exists
            $cleanOldCode = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $oldCode));
            $cleanNewCode = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $newCode));
            $oldUploadDir = public_path("uploads/companies/{$cleanOldCode}");
            $newUploadDir = public_path("uploads/companies/{$cleanNewCode}");
            if (is_dir($oldUploadDir)) {
                $parentDir = dirname($newUploadDir);
                if (!is_dir($parentDir)) {
                    @mkdir($parentDir, 0755, true);
                }
                @rename($oldUploadDir, $newUploadDir);
                
                // Update paths in $data for existing files to match the new folder name
                foreach (['bank_qr_1', 'bank_qr_2', 'bank_qr_3', 'logo_path', 'favicon_path'] as $file) {
                    if ($company->{$file} && str_contains($company->{$file}, "uploads/companies/{$cleanOldCode}/")) {
                        $data[$file] = str_replace(
                            "uploads/companies/{$cleanOldCode}/",
                            "uploads/companies/{$cleanNewCode}/",
                            $company->{$file}
                        );
                    }
                }
            }

            // Update file paths inside the tenant's database tables
            try {
                // Configure dynamic database connection configuration for the update
                $config = config("database.connections.central");
                $config['database'] = $newDb;
                config(["database.connections.tenant_path_update" => $config]);
                
                // Clear DB connection cache
                DB::purge('tenant_path_update');
                
                $oldPathPart = "uploads/companies/{$cleanOldCode}/";
                $newPathPart = "uploads/companies/{$cleanNewCode}/";
                
                // 1. Update settings table
                DB::connection('tenant_path_update')
                    ->table('settings')
                    ->where('value', 'LIKE', "%{$oldPathPart}%")
                    ->get()
                    ->each(function ($setting) use ($oldPathPart, $newPathPart) {
                        $newValue = str_replace($oldPathPart, $newPathPart, $setting->value);
                        DB::connection('tenant_path_update')
                            ->table('settings')
                            ->where('key', $setting->key)
                            ->update(['value' => $newValue]);
                    });
                
                // 2. Update products table
                DB::connection('tenant_path_update')
                    ->table('products')
                    ->where('image', 'LIKE', "%{$oldPathPart}%")
                    ->get()
                    ->each(function ($product) use ($oldPathPart, $newPathPart) {
                        $newImage = str_replace($oldPathPart, $newPathPart, $product->image);
                        DB::connection('tenant_path_update')
                            ->table('products')
                            ->where('id', $product->id)
                            ->update(['image' => $newImage]);
                    });
                
                DB::purge('tenant_path_update');
            } catch (\Exception $pathEx) {
                \Illuminate\Support\Facades\Log::error("Failed to update tenant file paths after code change: " . $pathEx->getMessage());
            }
        }

        $company->update($data);

        // Synchronize updated company settings to the tenant's local settings database table
        try {
            $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
            
            // Check if tenant database exists
            $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $exists = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
            } else {
                $exists = DB::connection('central')->select("SELECT 1 FROM information_schema.schemata WHERE schema_name = ?", [$tenantDb]);
            }
            if (!empty($exists)) {
                // Configure dynamic database connection configuration for the update
                $config = config("database.connections.central");
                $config['database'] = $tenantDb;
                config(["database.connections.tenant_update" => $config]);
                
                // Clear DB connection cache
                DB::purge('tenant_update');
                
                $settings = [
                    'store_name' => ['value' => $company->name, 'type' => 'text'],
                    'min_order_value' => ['value' => $company->min_purchase ?: '3800', 'type' => 'number'],
                    'store_phone' => ['value' => $company->contact_1 ?: '+91 9998887776', 'type' => 'text'],
                    'store_email' => ['value' => $company->email_1 ?: 'crackerdemo@gmail.com', 'type' => 'text'],
                    'store_address' => ['value' => $company->address_1 ?: 'Virudhunagar to Sivakasi Main Road, Sivakasi', 'type' => 'textarea'],
                ];
                
                $wa = $company->wa_link ?: $company->contact_1;
                if ($wa) {
                    $cleanWa = preg_replace('/[^0-9]/', '', $wa);
                    if (!empty($cleanWa)) {
                        $settings['store_whatsapp'] = ['value' => $cleanWa, 'type' => 'text'];
                    }
                }
                
                $settings['store_upi'] = ['value' => $company->bank_acc_1 ? ($company->bank_acc_1 . '@okaxis') : 'aathishacrackers@okaxis', 'type' => 'text'];
                $settings['bank_name'] = ['value' => $company->bank_name_1 ?: 'State Bank of India', 'type' => 'text'];
                $settings['bank_acc_no'] = ['value' => $company->bank_acc_1 ?: '1234567890', 'type' => 'text'];
                $settings['bank_ifsc'] = ['value' => $company->bank_ifsc_1 ?: 'SBIN0000123', 'type' => 'text'];
                $settings['bank_holder'] = ['value' => $company->bank_holder_1 ?: $company->name, 'type' => 'text'];
                
                for ($i = 1; $i <= 5; $i++) {
                    $codeField = "promo_code_{$i}";
                    $valField = "promo_value_{$i}";
                    if (!empty($company->{$codeField})) {
                        $settings[$codeField] = ['value' => $company->{$codeField}, 'type' => 'text'];
                    } else {
                        $settings[$codeField] = ['value' => '', 'type' => 'text'];
                    }
                    if (!empty($company->{$valField})) {
                        $settings[$valField] = ['value' => $company->{$valField}, 'type' => 'text'];
                    } else {
                        $settings[$valField] = ['value' => '', 'type' => 'text'];
                    }
                }
                
                $socialMap = [
                    'facebook_link' => 'fb_link',
                    'twitter_link' => 'tw_link',
                    'youtube_link' => 'yt_link',
                    'whatsapp_link' => 'wa_link',
                    'instagram_link' => 'ig_link',
                ];
                foreach ($socialMap as $settingKey => $compField) {
                    $settings[$settingKey] = ['value' => $company->{$compField} ?: '', 'type' => 'text'];
                }
                
                if (!empty($company->theme)) {
                    $settings['admin_theme'] = ['value' => $company->theme === 'Theme_1' ? 'gold' : ($company->theme === 'Theme_2' ? 'blue' : ($company->theme === 'Theme_3' ? 'emerald' : 'gold')), 'type' => 'text'];
                }
                if (!empty($company->tagline)) {
                    $settings['banner_scroller'] = ['value' => $company->tagline, 'type' => 'text'];
                }
                
                foreach ($settings as $key => $sData) {
                    DB::connection('tenant_update')
                        ->table('settings')
                        ->updateOrInsert(
                            ['key' => $key],
                            ['value' => $sData['value'], 'type' => $sData['type']]
                        );
                }
                
                DB::purge('tenant_update');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to sync updated details to tenant database settings: " . $e->getMessage());
        }

        return redirect()->route('admin_sys.company.index')->with('success', 'Domain company details updated successfully!');
    }

    /**
     * Delete a company domain record.
     */
    public function destroy($id)
    {
        \Illuminate\Support\Facades\Log::info("CompanyController@destroy started for ID $id");
        $company = Company::findOrFail($id);

        // Guard: Prevent deleting the last remaining company profile
        if (Company::count() <= 1) {
            \Illuminate\Support\Facades\Log::warning("Prevented deletion of company profile {$company->code} because it is the last remaining company.");
            return redirect()->route('admin_sys.company.index')->with('error', 'The last remaining company profile cannot be deleted.');
        }
        
        // 1. Delete uploaded files
        \Illuminate\Support\Facades\Log::info("Deleting uploaded files for company {$company->code}");
        $files = ['bank_qr_1', 'bank_qr_2', 'bank_qr_3', 'logo_path', 'favicon_path'];
        foreach ($files as $file) {
            $path = $company->{$file};
            if ($path && file_exists(public_path($path))) {
                @unlink(public_path($path));
            }
        }

        // 2. Drop the tenant database
        $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
        \Illuminate\Support\Facades\Log::info("Dropping database $tenantDb for company {$company->code}");
        try {
            $driver = DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                // First terminate active connections to the database in PostgreSQL
                \Illuminate\Support\Facades\Log::info("Terminating active connections for $tenantDb");
                DB::connection('central')->statement("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?", [$tenantDb]);
                
                // Drop database forcing connection termination
                \Illuminate\Support\Facades\Log::info("Executing DROP DATABASE statement for $tenantDb");
                DB::connection('central')->statement("DROP DATABASE IF EXISTS $tenantDb WITH (FORCE)");
            } else {
                // MySQL drop database
                \Illuminate\Support\Facades\Log::info("Executing DROP DATABASE statement for $tenantDb (MySQL)");
                DB::connection('central')->statement("DROP DATABASE IF EXISTS $tenantDb");
            }
            \Illuminate\Support\Facades\Log::info("Database $tenantDb dropped successfully or skipped if not exists");
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to drop database for company {$company->code}: " . $e->getMessage());
        }

        // 3. Delete the company record from central DB
        \Illuminate\Support\Facades\Log::info("Deleting company record {$company->code} from central companies table");
        $company->delete();
        \Illuminate\Support\Facades\Log::info("Company record {$company->code} deleted successfully");

        return redirect()->route('admin_sys.company.index')->with('success', 'Domain company profile and database deleted successfully!');
    }

    /**
     * Toggle the status of the specified company.
     */
    public function toggleStatus($id)
    {
        $company = Company::findOrFail($id);
        $company->status = ($company->status === 'active') ? 'inactive' : 'active';
        $company->save();

        return response()->json([
            'success' => true,
            'status' => $company->status,
            'message' => "Company '{$company->name}' status is now {$company->status}!"
        ]);
    }

    /**
     * Ensure the companies table is dynamically created and seeded if missing.
     */
    private function ensureTableExists()
    {
        // 1. Check if table exists and has all required columns. If not, drop and recreate.
        $hasTable = Schema::connection('central')->hasTable('companies');
        $needsRecreate = false;
        
        if ($hasTable) {
            $requiredColumns = ['code', 'smtp_host', 'tagline', 'logo_icon', 'logo_path', 'favicon_path'];
            foreach ($requiredColumns as $col) {
                if (!Schema::connection('central')->hasColumn('companies', $col)) {
                    $needsRecreate = true;
                    break;
                }
            }
        }

        if ($hasTable && $needsRecreate) {
            Schema::connection('central')->dropIfExists('companies');
            $hasTable = false;
        }

        if (!$hasTable) {
            Schema::connection('central')->create('companies', function ($table) {
                $table->id();
                
                // Main Info
                $table->string('code', 100)->unique();
                $table->string('name', 150);
                $table->string('website', 150);
                $table->string('gst_number', 50)->nullable();
                $table->string('pan_number', 50)->nullable();
                $table->string('msme_number', 50)->nullable();
                $table->string('theme', 50)->nullable();
                $table->string('type', 50)->nullable();
                $table->string('status', 50)->default('active');
                $table->text('tagline')->nullable();
                
                // Contacts
                $table->string('contact_1', 50)->nullable();
                $table->string('contact_2', 50)->nullable();
                $table->string('contact_3', 50)->nullable();
                $table->string('contact_4', 50)->nullable();
                $table->string('contact_5', 50)->nullable();
                $table->string('email_1', 150)->nullable();
                $table->string('email_2', 150)->nullable();
                $table->text('address_1')->nullable();
                $table->text('address_2')->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->text('map_link')->nullable();

                // Bank Account 1
                $table->text('bank_qr_1')->nullable();
                $table->string('bank_name_1', 150)->nullable();
                $table->string('bank_ifsc_1', 30)->nullable();
                $table->string('bank_acc_1', 50)->nullable();
                $table->string('bank_branch_1', 150)->nullable();
                $table->string('bank_type_1', 50)->nullable();
                $table->string('bank_holder_1', 150)->nullable();
                
                // Bank Account 2
                $table->text('bank_qr_2')->nullable();
                $table->string('bank_name_2', 150)->nullable();
                $table->string('bank_ifsc_2', 30)->nullable();
                $table->string('bank_acc_2', 50)->nullable();
                $table->string('bank_branch_2', 150)->nullable();
                $table->string('bank_type_2', 50)->nullable();
                $table->string('bank_holder_2', 150)->nullable();
                
                // Bank Account 3
                $table->text('bank_qr_3')->nullable();
                $table->string('bank_name_3', 150)->nullable();
                $table->string('bank_ifsc_3', 30)->nullable();
                $table->string('bank_acc_3', 50)->nullable();
                $table->string('bank_branch_3', 150)->nullable();
                $table->string('bank_type_3', 50)->nullable();
                $table->string('bank_holder_3', 150)->nullable();

                // Promos
                $table->string('promo_code_1', 100)->nullable();
                $table->text('promo_value_1')->nullable();
                $table->string('promo_code_2', 100)->nullable();
                $table->text('promo_value_2')->nullable();
                $table->string('promo_code_3', 100)->nullable();
                $table->text('promo_value_3')->nullable();
                $table->string('promo_code_4', 100)->nullable();
                $table->text('promo_value_4')->nullable();
                $table->string('promo_code_5', 100)->nullable();
                $table->text('promo_value_5')->nullable();

                // SMTP
                $table->text('smtp_host')->nullable();
                $table->string('smtp_port', 10)->nullable();
                $table->text('smtp_user')->nullable();
                $table->text('smtp_pass')->nullable();
                $table->string('smtp_ssl', 20)->nullable();

                // SMS
                $table->string('sms_header', 20)->nullable();
                $table->text('sms_apikey')->nullable();
                $table->string('sms_balance', 50)->nullable();

                // Other
                $table->string('min_purchase', 50)->nullable();
                $table->string('tax_calc', 50)->nullable();
                $table->string('delivery_calc', 50)->nullable();

                // Socials Overview
                $table->text('fb_link')->nullable();
                $table->text('tw_link')->nullable();
                $table->text('yt_link')->nullable();
                $table->text('wa_link')->nullable();
                $table->text('ig_link')->nullable();
                $table->text('pin_link')->nullable();
                $table->text('copyright_text')->nullable();
                $table->text('logo_path')->nullable();
                $table->text('favicon_path')->nullable();
                $table->string('logo_icon', 100)->nullable();

                $table->timestamps();
            });
        }

        // 2. Ensure default seeded companies exist only if the table is completely empty
        if (Company::count() === 0) {
            Company::create([
                'code' => 'crackersdemo',
                'name' => 'Crackers Demo',
                'website' => 'localhost:7001',
                'status' => 'active',
                'tagline' => 'Fresh and Warm Bakes Everyday',
                'logo_icon' => 'fa-solid fa-fire-burner',
            ]);

            Company::create([
                'code' => 'mahesh',
                'name' => 'Mahesh Bakery',
                'website' => 'localhost:7002',
                'status' => 'active',
                'contact_1' => '9442189007',
                'email_1' => 'mbcakes@gmail.com',
                'tagline' => 'Freshly Baked Goodness',
                'logo_icon' => 'fa-solid fa-fire-burner',
            ]);

            Company::create([
                'code' => 'achammal',
                'name' => 'Sri Achammal Pyrotech',
                'website' => 'localhost:7003',
                'status' => 'active',
                'contact_1' => '8531856635',
                'email_1' => 'sriachammalpyrotech@gmail.com',
                'tagline' => 'Sivakasi Online Booking',
                'logo_icon' => 'fa-solid fa-fire-burner',
            ]);
        }

        // Self-heal websites to localhost:7001, localhost:7002, localhost:7003
        $defaultWebsites = [
            'crackersdemo' => 'localhost:7001',
            'mahesh' => 'localhost:7002',
            'achammal' => 'localhost:7003'
        ];
        foreach ($defaultWebsites as $code => $site) {
            Company::where('code', $code)->update(['website' => $site]);
        }

        // Always ensure any default "aathish" records are cleaned up from database
        Company::where('code', 'aathish')
            ->orWhere('code', 'LIKE', 'aathish%')
            ->delete();
    }
}
