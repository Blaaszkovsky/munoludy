<?php

use App\Models\Edition;
use App\Models\Participant;
use App\Services\UserCom\UserComClient;
use App\Services\UserCom\UserComSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('creates new user, fills attributes and subscribes to list', function () {
    $this->seed();
    Http::fake([
        '*/api/public/users/search/*' => Http::response([], 404),
        '*/api/public/users/' => Http::response(['id' => 'abc123']),
        '*/api/public/users/abc123/*' => Http::response([]),
    ]);

    $edition = Edition::active();
    $participant = Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'test@gmail.com',
        'link_hash' => str_repeat('a', 40),
        'access_code' => '123456',
        'consented_marketing' => true,
    ]);

    $service = new UserComSyncService(new UserComClient());
    expect($service->sync($participant))->toBeTrue();
    expect($participant->fresh()->user_com_user_id)->toBe('abc123');

    Http::assertSent(function ($request) use ($edition) {
        return str_contains($request->url(), '/api/public/users/')
            && $request->method() === 'POST'
            && str_contains($request->header('Authorization')[0] ?? '', 'Token ')
            && ($request->data()[$edition->user_com_link_field] ?? null) !== null
            && ($request->data()[$edition->user_com_code_field] ?? null) === '123456'
            && ($request->data()['Marketing email'] ?? null) === true;
    });
});

it('when user exists patches only missing attributes and subscribes', function () {
    $this->seed();

    Http::fake([
        '*/api/public/users/search/*' => Http::response([
            'id' => 'existing-42',
            'email' => 'old@gmail.com',
            'attributes' => [
                ['name' => 'munoludy2026_link', 'value' => 'https://already-set/'],
            ],
        ]),
        '*/api/public/users/existing-42/*' => Http::response([]),
    ]);

    $edition = Edition::active();
    $participant = Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'old@gmail.com',
        'link_hash' => str_repeat('b', 40),
        'access_code' => '555000',
    ]);

    $service = new UserComSyncService(new UserComClient());
    expect($service->sync($participant))->toBeTrue();
    expect($participant->fresh()->user_com_user_id)->toBe('existing-42');

    Http::assertSent(function ($request) {
        if ($request->method() !== 'PATCH') return false;
        $data = $request->data();
        return array_key_exists('munoludy2026_kod', $data)
            && !array_key_exists('munoludy2026_link', $data);
    });
});

it('sends tag after voting', function () {
    $this->seed();
    Http::fake([
        '*/api/public/users/abc999/tag/*' => Http::response([]),
        '*/api/public/users/*/tags/*' => Http::response([]),
    ]);

    $edition = Edition::active();
    $participant = Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'voter@gmail.com',
        'link_hash' => str_repeat('c', 40),
        'access_code' => '777888',
        'user_com_user_id' => 'abc999',
    ]);

    $service = new UserComSyncService(new UserComClient());
    expect($service->tagVoted($participant, 189))->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/api/public/users/abc999/tag')
            && $request->method() === 'POST';
    });
});
