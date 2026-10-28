<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('faqs page renders the accordion content', function () {
    $this->get(route('faqs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Faq')
            ->has('faqs', 9)
            ->where('faqs.0.question', 'Are your shots 100% natural?')
            ->where('faqs.5.question', 'Is there a minimum purchase limit?')
            ->where('faqs.5.answer', fn (string $answer): bool => str_contains($answer, '10 shots'))
        );
});

test('footer faqs route is available', function () {
    $this->get('/faqs')->assertOk();
});
