<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Laravel\LaravelHttpTransport;

it('sends a GET with the query and returns status, body and lower-case headers', function () {
    Http::fake(['example.test/*' => Http::response('{"ok":true}', 200, ['Retry-After' => '4'])]);

    $response = (new LaravelHttpTransport(retryDelayMs: 0))->get('https://example.test/search', ['q' => 'a b', 'skip' => null]);

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe('{"ok":true}')
        ->and($response->header('Retry-After'))->toBe('4');

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === 'https://example.test/search?q=a%20b'
        && $request->hasHeader('Accept', 'application/json'));
});

it('retries after a 429 and returns the next answer', function () {
    Http::fake(['*' => Http::sequence()->push('{}', 429)->push('{"ok":true}', 200)]);

    $response = (new LaravelHttpTransport(attempts: 3, retryDelayMs: 0))->get('https://example.test/x');

    expect($response->status)->toBe(200);
    Http::assertSentCount(2);
});

it('returns the last 429 when the attempts run out', function () {
    Http::fake(['*' => Http::response('{}', 429, ['Retry-After' => '1'])]);

    $response = (new LaravelHttpTransport(attempts: 2, retryDelayMs: 0))->get('https://example.test/x');

    expect($response->status)->toBe(429)->and($response->header('Retry-After'))->toBe('1');
    Http::assertSentCount(2);
});

it('does not retry other errors', function () {
    Http::fake(['*' => Http::response('{}', 500)]);

    $response = (new LaravelHttpTransport(attempts: 3, retryDelayMs: 0))->get('https://example.test/x');

    expect($response->status)->toBe(500);
    Http::assertSentCount(1);
});

it('wraps connection failures in ApiException', function () {
    Http::fake(fn () => throw new ConnectionException('cannot connect'));

    (new LaravelHttpTransport(retryDelayMs: 0))->get('https://example.test/x');
})->throws(ApiException::class, 'cannot connect');

it('wraps request exceptions in ApiException', function () {
    Http::fake(fn () => throw new RequestException(new Illuminate\Http\Client\Response(new Response(500))));

    (new LaravelHttpTransport(retryDelayMs: 0))->get('https://example.test/x');
})->throws(ApiException::class, 'Network error');
