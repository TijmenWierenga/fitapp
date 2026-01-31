<?php

test('pages include open graph meta tags', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('<meta property="og:type" content="website">', false);
    $response->assertSee('<meta property="og:title" content="', false);
    $response->assertSee('<meta property="og:description" content="', false);
    $response->assertSee('<meta property="og:image" content="', false);
    $response->assertSee('og-image.png', false);
});

test('pages include twitter card meta tags', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $response->assertSee('<meta name="twitter:title" content="', false);
    $response->assertSee('<meta name="twitter:description" content="', false);
    $response->assertSee('<meta name="twitter:image" content="', false);
});

test('pages include primary meta description', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('<meta name="description" content="', false);
    $response->assertSee('<meta name="theme-color" content="#18181b">', false);
});
