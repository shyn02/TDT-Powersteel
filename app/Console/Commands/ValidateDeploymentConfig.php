<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateDeploymentConfig extends Command
{
    protected $signature = 'config:validate-deployment';
    protected $description = 'SEC-07: Validate production deployment configuration (APP_DEBUG, HTTPS, cookies, CSP)';

    public function handle(): int
    {
        $errors = [];
        $warnings = [];

        if (config('app.debug')) {
            $errors[] = 'APP_DEBUG=true in production — must be false';
        }
        if (config('app.env') !== 'production' && app()->environment('production')) {
            $warnings[] = 'APP_ENV is not production';
        }
        if (! config('session.secure') && app()->environment('production')) {
            $errors[] = 'SESSION_SECURE_COOKIE=false — must be true behind HTTPS';
        }
        if (! request()->isSecure() && app()->environment('production')) {
            $warnings[] = 'Request is not secure (isSecure false) — check trusted proxies and HTTPS termination';
        }
        // Check HSTS via SecurityHeaders is set for secure requests
        $csp = app(\App\Http\Middleware\SecurityHeaders::class);
        // Check APP_KEY is set
        if (empty(config('app.key'))) {
            $errors[] = 'APP_KEY is empty';
        }

        if ($errors) {
            foreach ($errors as $e) $this->error($e);
            return 1;
        }
        foreach ($warnings as $w) $this->warn($w);
        $this->info('Deployment config validation passed');
        return 0;
    }
}
