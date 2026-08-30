<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure APP_KEY has a fallback if not defined in the environment (e.g. Render Free Tier)
        if (empty(config('app.key')) || config('app.key') === 'SomeRandomString' || config('app.key') === 'null') {
            config(['app.key' => 'base64:mmOr9QdwTgiYlUU7ku/etwsCl5ryOSa25AfLUEeHo6g=']);
        }

        // Ensure session.driver falls back to 'file' to avoid missing database tables in default configurations
        if (empty(config('session.driver')) || config('session.driver') === 'database') {
            config(['session.driver' => 'file']);
        }

        // 1. Clone the default connection config to 'central'
        try {
            $defaultConn = config('database.default');
            $connConfig = config("database.connections.{$defaultConn}");
            config(["database.connections.central" => $connConfig]);
        } catch (\Exception $e) {
            // Config not set up
        }

        // Share null as default to prevent view variable errors
        view()->share('currentCompany', null);

        // 2. Set SMTP mail config fallback if environment variables are not defined (e.g. on Render)
        if (empty(config('mail.mailers.smtp.username')) || config('mail.mailers.smtp.username') === 'null') {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => 'smtp.gmail.com',
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.encryption' => 'tls',
                'mail.mailers.smtp.username' => 'athiraj.vnr@gmail.com',
                'mail.mailers.smtp.password' => 'qbvgpfmjuroltsvv',
                'mail.from.address' => 'athiraj.vnr@gmail.com',
                'mail.from.name' => 'Athiraj',
            ]);
        }

        // 3. Auto-correct Gmail SMTP settings & SSL stream options for Hostinger compatibility
        config([
            'mail.mailers.sendmail' => [
                'transport' => 'sendmail',
                'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
            ],
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ]);
        if (config('mail.mailers.smtp.host') === 'smtp.gmail.com') {
            config([
                'mail.mailers.smtp.password' => str_replace(' ', '', config('mail.mailers.smtp.password')),
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.encryption' => 'tls',
            ]);
        }
    }

}
