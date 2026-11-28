<?php

use App\Models\User;

test('the landing page is server-rendered with its SEO tags', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="pt-BR"', false)
        ->assertSee('<title>FalaDev: simulador de entrevista técnica em inglês para devs</title>', false)
        ->assertSee('name="description"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('"@type": "FAQPage"', false)
        ->assertSee('Simulador de entrevista em inglês para devs');
});

test('guests are sent to sign up, signed-in users to a new interview', function () {
    $this->get(route('home'))->assertSee(route('register'), false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee(route('interviews.create'), false)
        ->assertSee(route('dashboard'), false);
});

test('the canonical link waits for a real domain', function () {
    config(['app.url' => 'http://localhost']);
    $this->get(route('home'))->assertDontSee('<link rel="canonical" href="http://localhost', false);

    config(['app.url' => 'https://faladev.com.br']);
    $this->get(route('home'))->assertSee('<link rel="canonical" href="https://faladev.com.br/">', false);
});

test('the privacy page explains what happens to the audio', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('O áudio não é guardado');
});

test('the sitemap lists the public pages', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('home'), false)
        ->assertSee(route('privacy'), false);
});

test('robots.txt points to the sitemap and keeps private areas out', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'), false)
        ->assertSee('Disallow: /interviews', false);
});
