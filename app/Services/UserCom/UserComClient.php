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
        return rtrim(SiteSetting::get('user_com_base_url', config('munoludy.user_com.base_url')), '/');
    }

    public function apiKey(): ?string
    {
        return SiteSetting::get('user_com_api_key', config('munoludy.user_com.api_key'));
    }

    protected function http(): PendingRequest
    {
        $client = Http::withHeaders([
                'Authorization' => 'Token '.$this->apiKey(),
                'Accept' => 'application/json',
            ])
            ->timeout(config('munoludy.user_com.timeout'))
            ->retry(
                config('munoludy.user_com.retry_times'),
                config('munoludy.user_com.retry_sleep'),
                throw: false
            );

        $cacert = storage_path('app/cacert.pem');
        if (is_file($cacert)) {
            $client = $client->withOptions(['verify' => $cacert]);
        }

        return $client;
    }

    public function findUserByEmail(string $email): ?array
    {
        $resp = $this->http()->get($this->baseUrl().'/api/public/users/search/', ['email' => $email]);
        if ($resp->successful()) {
            $json = $resp->json();
            if (\is_array($json) && isset($json['id'])) {
                return $json;
            }
            if (\is_array($json) && isset($json['results']) && \count($json['results']) > 0) {
                return $json['results'][0];
            }
        }

        if ($resp->status() !== 404) {
            Log::warning('user.com find failed', [
                'email' => $email,
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 500),
            ]);
        }

        return null;
    }

    public function createUser(array $attrs): ?array
    {
        $resp = $this->http()->post($this->baseUrl().'/api/public/users/', $attrs);
        if (!$resp->successful()) {
            Log::error('user.com create failed', [
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 500),
            ]);
            return null;
        }
        return $resp->json();
    }

    public function updateUser(string $userId, array $attrs): bool
    {
        $resp = $this->http()->patch($this->baseUrl().'/api/public/users/'.$userId.'/', $attrs);
        if (!$resp->successful()) {
            Log::error('user.com update failed', [
                'user_id' => $userId,
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 500),
            ]);
        }
        return $resp->successful();
    }

    public function subscribeToList(string $userId, int $listId): bool
    {
        $resp = $this->http()->post(
            $this->baseUrl().'/api/public/users/'.$userId.'/add_to_list/',
            ['list' => $listId]
        );

        if (!$resp->successful()) {
            Log::warning('user.com subscribe failed', [
                'user_id' => $userId,
                'list_id' => $listId,
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 500),
            ]);
        }

        return $resp->successful();
    }

    public function addTag(string $userId, string $tagName): bool
    {
        $resp = $this->http()->post(
            $this->baseUrl().'/api/public/users/'.$userId.'/add_tag/',
            ['name' => $tagName]
        );

        if (!$resp->successful()) {
            Log::warning('user.com tag failed', [
                'user_id' => $userId,
                'tag_name' => $tagName,
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 500),
            ]);
        }

        return $resp->successful();
    }
}
