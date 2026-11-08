<?php

use Inertia\Testing\AssertableInertia as Assert;

test('privacy page explains how Wellth handles customer data', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('<title>Privacy Policy · '.config('app.name').'</title>', false)
        ->assertSee('Ghana Data Protection Act', false)
        ->assertSee('admin@wellthgh.com', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Privacy')
            ->where('updated', 'November 2026')
            ->has('sections')
            ->where('sections.0.heading', 'Data Collected')
        );
});
