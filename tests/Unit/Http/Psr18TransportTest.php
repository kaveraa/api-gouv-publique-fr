<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

function stubClient(ResponseInterface|Throwable $result): ClientInterface
{
    return new class($result) implements ClientInterface
    {
        public ?RequestInterface $last = null;

        public function __construct(private ResponseInterface|Throwable $result) {}

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $this->last = $request;

            return $this->result instanceof Throwable ? throw $this->result : $this->result;
        }
    };
}

it('builds an encoded url, skips null values and lower-cases header names', function () {
    $client = stubClient(new PsrResponse(200, ['Retry-After' => '3'], '{"ok":true}'));
    $transport = new Psr18Transport($client, new Psr17Factory);

    $response = $transport->get('https://example.test/search', ['q' => "rue de l'Eglise & co", 'page' => 2, 'skip' => null]);

    expect((string) $client->last->getUri())->toBe('https://example.test/search?q=rue%20de%20l%27Eglise%20%26%20co&page=2')
        ->and($client->last->getHeaderLine('Accept'))->toBe('application/json')
        ->and($response->status)->toBe(200)
        ->and($response->body)->toBe('{"ok":true}')
        ->and($response->header('Retry-After'))->toBe('3');
});

it('encodes accented and reserved characters in the query', function () {
    $client = stubClient(new PsrResponse(200, [], '{}'));

    (new Psr18Transport($client, new Psr17Factory))->get('https://example.test/search', ['q' => "\u{C9}glise & \u{C9}cully"]);

    expect((string) $client->last->getUri())->toBe('https://example.test/search?q=%C3%89glise%20%26%20%C3%89cully');
});

it('wraps client exceptions in ApiException', function () {
    $failure = new class('boom') extends RuntimeException implements ClientExceptionInterface {};
    $transport = new Psr18Transport(stubClient($failure), new Psr17Factory);

    try {
        $transport->get('https://example.test/x');
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect($e->getMessage())->toContain('boom')
            ->and($e->getCode())->toBe(0)
            ->and($e->getPrevious())->toBe($failure);
    }
});
