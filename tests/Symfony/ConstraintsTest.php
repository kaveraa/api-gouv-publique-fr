<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExiste;
use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExisteValidator;
use Kaveraa\ApiGouv\Symfony\Validator\Siren;
use Kaveraa\ApiGouv\Symfony\Validator\SirenValidator;
use Kaveraa\ApiGouv\Symfony\Validator\Siret;
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Kaveraa\ApiGouv\Tests\Symfony\MockResponses;
use Kaveraa\ApiGouv\Tests\Symfony\NoValidatorBundle;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

it('validates SIREN and SIRET values', function () {
    $validator = $this->boot()->get(ValidatorInterface::class);

    expect($validator->validate('812487973', new Siren))->toHaveCount(0)
        ->and($validator->validate('812 487 973', new Siren))->toHaveCount(0)
        ->and($validator->validate("812\u{A0}487\u{A0}973", new Siren))->toHaveCount(0)
        ->and($validator->validate('812487974', new Siren))->toHaveCount(1)
        ->and($validator->validate('abc', new Siren))->toHaveCount(1)
        ->and($validator->validate(['x'], new Siren))->toHaveCount(1)
        ->and($validator->validate(null, new Siren))->toHaveCount(0)
        ->and($validator->validate('', new Siren))->toHaveCount(0)
        ->and($validator->validate('81248797300040', new Siret))->toHaveCount(0)
        ->and($validator->validate('35600000000001', new Siret))->toHaveCount(0)
        ->and($validator->validate('81248797300041', new Siret))->toHaveCount(1)
        ->and($validator->validate(null, new Siret))->toHaveCount(0);
});

it('translates the messages in English and French', function () {
    $container = $this->boot();
    $validator = $container->get(ValidatorInterface::class);

    $english = (string) $validator->validate('123', new Siren)->get(0)->getMessage();
    $container->get(TranslatorInterface::class)->setLocale('fr');
    $french = (string) $validator->validate('123', new Siret)->get(0)->getMessage();

    expect($english)->toBe('This value must be a valid SIREN number (9 digits).')
        ->and($french)->toBe('Cette valeur doit être un numéro SIRET valide (14 chiffres).');
});

it('accepts a custom message', function () {
    $validator = $this->boot()->get(ValidatorInterface::class);

    expect((string) $validator->validate('123', new Siren(message: 'Bad number'))->get(0)->getMessage())->toBe('Bad number');
});

it('checks that the company exists through the API', function () {
    MockResponses::$queue[] = new MockResponse(loadFixture('entreprises_siren.json'));
    MockResponses::$queue[] = new MockResponse(loadFixture('entreprises_empty.json'));
    $validator = $this->boot()->get(ValidatorInterface::class);

    $found = $validator->validate('812487973', new EntrepriseExiste);
    $unknown = $validator->validate('812487973', new EntrepriseExiste);

    expect($found)->toHaveCount(0)
        ->and($unknown)->toHaveCount(1)
        ->and((string) $unknown->get(0)->getMessage())->toBe('This value does not match any known company.')
        ->and(MockResponses::$urls)->toHaveCount(2);
});

it('fails closed when the company service is unavailable', function (int $status) {
    MockResponses::$queue[] = new MockResponse('{}', ['http_code' => $status]);
    $validator = $this->boot()->get(ValidatorInterface::class);

    $violations = $validator->validate('812487973', new EntrepriseExiste);

    expect($violations)->toHaveCount(1)
        ->and((string) $violations->get(0)->getMessage())->toBe('This value could not be verified because the company service is unavailable.');
})->with([429, 500, 503]);

it('rejects a malformed number without calling the API', function () {
    $validator = $this->boot()->get(ValidatorInterface::class);

    $violations = $validator->validate('12', new EntrepriseExiste);

    expect($violations)->toHaveCount(1)
        ->and((string) $violations->get(0)->getMessage())->toBe('This value must be a valid SIREN number (9 digits).')
        ->and(MockResponses::$urls)->toBe([]);
});

it('works with the fake mode', function () {
    $container = $this->boot(['fake' => true], mockHttp: false);
    $container->get(FakeApiGouv::class)->entreprises()->with(Factories::entreprise(['siren' => '123456782']));
    $validator = $container->get(ValidatorInterface::class);

    expect($validator->validate('123456782', new EntrepriseExiste))->toHaveCount(0)
        ->and($validator->validate('812487973', new EntrepriseExiste))->toHaveCount(1);
});

it('registers no validator service when the Validator component is reported absent', function () {
    $container = $this->boot(bundleClass: NoValidatorBundle::class);

    expect($container->has(SirenValidator::class))->toBeFalse()
        ->and($container->has(EntrepriseExisteValidator::class))->toBeFalse();
});
