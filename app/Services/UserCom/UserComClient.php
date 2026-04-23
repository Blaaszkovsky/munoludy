<?php

namespace App\Services\UserCom;

use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserComClient
{
    public function baseUrl(): string
    {
        return SiteSetting::get('user_com_base_url', config('munoludy.user_com.base_url'));
    }

    public function apiKey(): ?string
    {
        return SiteSetting::get('user_com_api_key', config('munoludy.user_com.api_key'));
    }

    protected function http(): PendingRequest
    {
        return Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(config('munoludy.user_com.timeout'))
            ->retry(config('munoludy.user_com.retry_times'), config('munoludy.user_com.retry_sleep'), throw: false);
    }

    public function findUserByEmail(string $email): ?array
    {
        $resp = $this->http()->get($this->baseUrl().'/api/public/users/', ['email' => $email]);
        if (!$resp->successful()) {
            Log::warning('user.com find failed', ['email' => $email, 'status' => $resp->status()]);
            return null;
        }
        return $resp->json('results.0');
    }

    public function createUser(array $attrs): ?array
    {
        $resp = $this->http()->post($this->baseUrl().'/api/public/users/', $attrs);
        if (!$resp->successful()) {
            Log::error('user.com create failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        return $resp->json();
    }

    public function updateUser(string $userId, array $attrs): bool
    {
        $resp = $this->http()->patch($this->baseUrl()."/api/public/users/$userId/", $attrs);
        return $resp->successful();
    }

    public function subscribeToList(string $userId, int $listId): bool
    {
        $resp = $this->http()->post($this->baseUrl()."/api/public/users/$userId/subscribe_to_list/", ['list_id' => $listId]);
        return $resp->successful();
    }
}
