<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Cache\ResponseCache;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Exceptions\RateLimitException;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\ArrayCache;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

function requester(FakeTransport $transport, ?ResponseCache $cache = null): Requester
{
    return new Requester($transport, 'https://example.test/api/', $cache, 60);
}

it('joins the base url and the path and returns decoded json', function () {
    $transport = new FakeTransport(FakeTransport::json('{"a":1}'));

    $data = requester($transport)->getJson('/search', ['q' => 'x']);

    expect($data)->toBe(['a' => 1])
        ->and($transport->calls[0])->toBe(['https://example.test/api/search', ['q' => 'x']]);
});

it('maps 404 to NotFoundException', function () {
    requester(new FakeTransport(FakeTransport::json('{}', 404)))->getJson('x');
})->throws(NotFoundException::class);

it('maps 429 to RateLimitException with the retry delay', function () {
    $transport = new FakeTransport(FakeTransport::json('{}', 429, ['retry-after' => '2']));

    try {
        requester($transport)->getJson('x');
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->retryAfter)->toBe(2)->and($e->getCode())->toBe(429);
    }
});

it('leaves the retry delay null when the header is missing or not a number', function (array $headers) {
    $transport = new FakeTransport(FakeTransport::json('{}', 429, $headers));

    try {
        requester($transport)->getJson('x');
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->retryAfter)->toBeNull();
    }
})->with([
    'missing header' => [[]],
    'not a number' => [['retry-after' => 'soon']],
]);

it('maps other errors to ApiException and keeps the api message', function () {
    $transport = new FakeTransport(FakeTransport::json('{"erreur":"per_page invalide"}', 400));

    try {
        requester($transport)->getJson('x');
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect($e->getCode())->toBe(400)->and($e->getMessage())->toContain('per_page invalide');
    }
});

it('cuts a long plain error body on a character boundary', function () {
    $transport = new FakeTransport(FakeTransport::json(str_repeat("\u{E9}", 300), 500));

    try {
        requester($transport)->getJson('x');
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect(preg_match('//u', $e->getMessage()))->toBe(1);
    }
});

it('maps 5xx to ApiException', function () {
    requester(new FakeTransport(FakeTransport::json('oops', 503)))->getJson('x');
})->throws(ApiException::class);

it('rejects invalid json and json that is not an object or list', function (string $body) {
    requester(new FakeTransport(FakeTransport::json($body)))->getJson('x');
})->with(['not json', '"text"', '12'])->throws(InvalidResponseException::class);

it('serves the second identical call from the cache', function () {
    $transport = new FakeTransport(FakeTransport::json('{"a":1}'));
    $requester = requester($transport, new ResponseCache(new ArrayCache));

    $requester->getJson('x', ['q' => 'a', 'p' => 1]);
    $again = $requester->getJson('x', ['p' => 1, 'q' => 'a']);

    expect($again)->toBe(['a' => 1])->and($transport->calls)->toHaveCount(1);
});

it('never caches a failed response', function () {
    $transport = new FakeTransport(FakeTransport::json('{}', 500), FakeTransport::json('{"ok":true}'));
    $requester = requester($transport, new ResponseCache(new ArrayCache));

    try {
        $requester->getJson('x');
    } catch (ApiException) {
    }

    expect($requester->getJson('x'))->toBe(['ok' => true]);
});
