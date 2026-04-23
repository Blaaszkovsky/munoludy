<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DisposableEmailChecker
{
    private const CACHE_KEY = 'disposable_domains_set';
    private const CACHE_TTL = 86400; // 24h

    public function isDisposable(string $email): bool
    {
        $parts = explode('@', strtolower(trim($email)));
        if (count($parts) !== 2) {
            return false;
        }
        $domain = $parts[1];
        return in_array($domain, $this->domainsSet(), true);
    }

    private function domainsSet(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $content = Storage::disk('local')->get('disposable-domains.txt') ?? '';
            $extra = \App\Models\SiteSetting::get('disposable_extra_domains', '');
            $domains = array_merge(
                explode("\n", $content),
                explode("\n", $extra),
            );
            return array_values(array_filter(array_map('trim', $domains), fn($d) => $d && !str_starts_with($d, '#')));
        });
    }
}
