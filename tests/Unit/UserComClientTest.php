<?php

use App\Models\Edition;
use App\Models\Participant;
use App\Services\UserCom\UserComClient;
use App\Services\UserCom\UserComSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('creates new user and subscribes to list', function () {
    $this->seed();
    Http::fake([
        '*/api/public/users/?*' => Http::response(['results' => []]),
        '*/api/public/users/' => Http::response(['id' => 'abc123']),
        '*/api/public/users/abc123/' => Http::response([]),
        '*/subscribe_to_list/' => Http::response([]),
    ]);

    $edition = Edition::active();
    $participant = Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'test@example.com',
        'link_hash' => str_repeat('a', 40),
        'access_code' => '123456',
    ]);

    $service = new UserComSyncService(new UserComClient());
    expect($service->sync($participant))->toBeTrue();
    expect($participant->fresh()->user_com_user_id)->toBe('abc123');
});
