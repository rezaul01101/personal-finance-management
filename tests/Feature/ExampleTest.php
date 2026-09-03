<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('includes the pwa manifest link', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
});
