<?php

use App\Models\Edition;
use App\Models\Participant;
use App\Services\UserCom\UserComClient;
use App\Services\UserCom\UserComSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('creates new user, sends marketing attribute and adds to list', function () {
    $this->seed();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/public/users/search/*' => Http::response([], 404),
        '*/api/public/users/' => Http::response(['id' => 'abc123']),
        '*/api/public/users/abc123/add_to_list/' => Http::response(['id' => 17], 201),
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
        return str_ends_with($request->url(), '/api/public/users/')
            && $request->method() === 'POST'
            && str_contains($request->header('Authorization')[0] ?? '', 'Token ')
            && ($request->data()[$edition->user_com_link_field] ?? null) !== null
            && ($request->data()[$edition->user_com_code_field] ?? null) === '123456'
            && ($request->data()['Marketing email'] ?? null) === true;
    });

    Http::assertSent(function ($request) {
        return str_ends_with($request->url(), '/add_to_list/')
            && $request->method() === 'POST'
            && ($request->data()['list'] ?? null) === 17;
    });
});

it('when user exists patches only missing attributes', function () {
    $this->seed();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/public/users/search/*' => Http::response([
            'id' => 'existing-42',
            'email' => 'old@gmail.com',
            'attributes' => [
                ['name' => 'munoludy2026_link', 'value' => 'https://already-set/'],
            ],
        ]),
        '*/api/public/users/existing-42/' => Http::response([]),
        '*/api/public/users/existing-42/add_to_list/' => Http::response(['id' => 17], 201),
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

it('sends add_tag with tag name after voting', function () {
    $this->seed();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/public/users/abc999/add_tag/' => Http::response([
            'created' => true,
            'tag' => ['name' => 'munoludy2026_voted'],
        ]),
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
    expect($service->tagVoted($participant, 'munoludy2026_voted'))->toBeTrue();

    Http::assertSent(function ($request) {
        return str_ends_with($request->url(), '/api/public/users/abc999/add_tag/')
            && $request->method() === 'POST'
            && ($request->data()['name'] ?? null) === 'munoludy2026_voted';
    });
});
