<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders landing page', function () {
    $this->seed();
    $this->get('/')->assertOk()->assertSee('Weź udział w plebiscycie');
});
