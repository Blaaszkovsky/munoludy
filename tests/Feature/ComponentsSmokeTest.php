<?php

it('renders layout smoke test', function () {
    $this->get('/_smoke/layout')
        ->assertOk()
        ->assertSee('Smoke test')
        ->assertSee('Biletomat');
});
