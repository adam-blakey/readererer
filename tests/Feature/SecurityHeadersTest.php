<?php

use App\Enums\UserRole;

it('sends the security headers on pages', function () {
    $response = $this->get('/login')->assertOk();

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("script-src 'self' 'nonce-")
        ->toContain("frame-ancestors 'none'")
        ->and($response->headers->all())->toMatchArray([
            'x-frame-options' => ['DENY'],
            'x-content-type-options' => ['nosniff'],
            'cross-origin-opener-policy' => ['same-origin'],
            'cross-origin-embedder-policy' => ['credentialless'],
            'cross-origin-resource-policy' => ['same-origin'],
        ])
        ->and($response->headers->has('Permissions-Policy'))->toBeTrue();
});

it('does not set the script-readable XSRF-TOKEN cookie', function () {
    expect(collect($this->get('/login')->headers->getCookies())->map->getName())
        ->not->toContain('XSRF-TOKEN');
});

it('sends redirects without a body', function () {
    $this->get('/dashboard')->assertRedirect()->assertContent('');
});

it('gives inline scripts the policy nonce and renders no inline event handlers', function () {
    $response = $this->actingAs(make_user(UserRole::Admin))->get(route('terms.create'))->assertOk();

    preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy'), $nonce);

    expect($response->getContent())
        ->toContain('<script nonce="'.$nonce[1].'">')
        ->not->toMatch('/<[^>]+\son[a-z]+="/');
});

it('allows only the image origins the page renders', function () {
    $user = make_user(attributes: ['image' => 'https://cdn.example.org:8443/me.png']);

    $policy = $this->actingAs($user)->get(route('users.show', $user))->assertOk()->headers->get('Content-Security-Policy');

    expect($policy)->toContain("img-src 'self' data: https://cdn.example.org:8443;")
        ->and($this->get(route('login'))->headers->get('Content-Security-Policy'))->toContain("img-src 'self' data:;");
});

it('leaves out image hosts that could break the policy', function () {
    $user = make_user(attributes: ['image' => 'https://evil.example;script-src *.example/me.png']);

    expect($this->actingAs($user)->get(route('users.show', $user))->headers->get('Content-Security-Policy'))
        ->toContain("img-src 'self' data:;")
        ->not->toContain('evil');
});
