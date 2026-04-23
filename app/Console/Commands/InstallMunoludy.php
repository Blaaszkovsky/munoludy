<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class InstallMunoludy extends Command
{
    protected $signature = 'munoludy:install
        {--skip-migrate : Skip running migrations and seeders}
        {--skip-disposable : Skip downloading disposable email domains list}
        {--force-admin-path : Regenerate ADMIN_PANEL_PATH even if already set}';

    protected $description = 'One-shot installer: migrate, seed, download disposable domains, set random admin path.';

    private const DISPOSABLE_LIST_URL =
        'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf';

    public function handle(): int
    {
        $this->line('Munoludy installer');
        $this->line('==================');

        if (! $this->option('skip-migrate')) {
            $this->info('-> Running migrations and seeders...');

            try {
                Artisan::call('migrate', ['--force' => true], $this->getOutput());
                Artisan::call('db:seed', ['--force' => true], $this->getOutput());
            } catch (\Throwable $e) {
                $this->error('Migration/seed failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if (! $this->option('skip-disposable')) {
            $this->downloadDisposableDomains();
        }

        $this->updateAdminPanelPath();

        $this->info('-> Clearing config cache...');
        Artisan::call('config:clear', [], $this->getOutput());

        $this->newLine();
        $this->info('Done. Admin panel path lives in .env as ADMIN_PANEL_PATH.');

        return self::SUCCESS;
    }

    private function downloadDisposableDomains(): void
    {
        $target = storage_path('app/disposable-domains.txt');

        if (is_file($target) && filesize($target) > 0) {
            $this->line('-> disposable-domains.txt already present; skipping download.');

            return;
        }

        $this->info('-> Downloading disposable email domains list...');

        try {
            $contents = @file_get_contents(self::DISPOSABLE_LIST_URL);

            if ($contents === false || $contents === '') {
                $this->warn('Could not fetch list; leaving empty file so app does not crash.');
                @file_put_contents($target, '');

                return;
            }

            @mkdir(dirname($target), 0755, true);
            file_put_contents($target, $contents);
            $this->info('   saved to '.$target);
        } catch (\Throwable $e) {
            $this->warn('Failed to download: '.$e->getMessage());
        }
    }

    private function updateAdminPanelPath(): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            $this->warn('No .env file found; skipping ADMIN_PANEL_PATH update.');

            return;
        }

        $env = (string) file_get_contents($envPath);

        $shouldReplace = $this->option('force-admin-path')
            || str_contains($env, 'ADMIN_PANEL_PATH=tobereplaced')
            || preg_match('/^ADMIN_PANEL_PATH=\s*$/m', $env);

        if (! $shouldReplace) {
            $this->line('-> ADMIN_PANEL_PATH already customised; keeping existing value.');

            return;
        }

        $slug = strtolower(Str::random(12));
        $slug = preg_replace('/[^a-z0-9]/', '', $slug) ?: strtolower(bin2hex(random_bytes(6)));

        $new = preg_replace(
            '/ADMIN_PANEL_PATH=.*/',
            'ADMIN_PANEL_PATH='.$slug,
            $env,
            1
        );

        if ($new === null || $new === $env) {
            $this->warn('Could not rewrite ADMIN_PANEL_PATH line.');

            return;
        }

        file_put_contents($envPath, $new);
        $this->info('-> ADMIN_PANEL_PATH set to /'.$slug);
    }
}
