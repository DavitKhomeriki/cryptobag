<?php

use App\Http\Middleware\EnsureSwaggerIsVisible;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

uses(TestCase::class);

test('it allows swagger documentation in local environments', function () {
    config(['app.env' => 'local']);
    $middleware = new EnsureSwaggerIsVisible;
    $request = Request::create('/api/documentation');

    $response = $middleware->handle($request, fn () => response('visible'));

    expect($response->getContent())->toBe('visible');
});

test('it hides swagger documentation outside local environments', function () {
    config(['app.env' => 'production']);
    $middleware = new EnsureSwaggerIsVisible;
    $request = Request::create('/api/documentation');

    expect(fn () => $middleware->handle($request, fn () => response('visible')))
        ->toThrow(NotFoundHttpException::class);
});
