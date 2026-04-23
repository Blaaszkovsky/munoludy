<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('admin dashboard loads with widgets without error', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path")->assertOk();
});

it('vote submissions resource loads', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path/vote-submissions")->assertOk();
});

it('answer groups resource loads', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path/answer-groups")->assertOk();
});

it('page content list loads', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path/page-contents")->assertOk();
});

it('page content edit page loads for each seeded view', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    foreach (\App\Models\PageContent::all() as $pc) {
        $this->actingAs($user)->get("/$path/page-contents/{$pc->id}/edit")->assertOk();
    }
});

it('results publisher page loads', function () {
    $this->seed();
    $path = config('munoludy.admin_path');
    $user = User::where('email', 'admin@muno.local')->first();
    $this->actingAs($user)->get("/$path/results-publisher")->assertOk();
});
