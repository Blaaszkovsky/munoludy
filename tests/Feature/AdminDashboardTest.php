<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->path = config('munoludy.admin_path');
    $this->admin = adminUser();
});

it('admin dashboard loads with widgets without error', function () {
    $this->actingAs($this->admin)->get("/{$this->path}")->assertOk();
});

it('vote submissions resource loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/vote-submissions")->assertOk();
});

it('answer groups resource loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/answer-groups")->assertOk();
});

it('page content list loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/page-contents")->assertOk();
});

it('page content edit page loads for each seeded view', function () {
    foreach (\App\Models\PageContent::all() as $pc) {
        $this->actingAs($this->admin)->get("/{$this->path}/page-contents/{$pc->id}/edit")->assertOk();
    }
});

it('results publisher page loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/results-publisher")->assertOk();
});
