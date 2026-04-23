<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects guests from admin panel', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $this->get("/$path")->assertRedirect();
});

it('allows super_admin to access panel', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path")->assertOk();
});
