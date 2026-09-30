<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Kaveraa\ApiGouv\Laravel\Rules\EntrepriseExiste;
use Kaveraa\ApiGouv\Laravel\Rules\Siren;
use Kaveraa\ApiGouv\Laravel\Rules\Siret;

function check(mixed $value, object $rule): Illuminate\Validation\Validator
{
    return Validator::make(['n' => $value], ['n' => [$rule]]);
}

it('passes valid SIREN and SIRET values', function () {
    expect(check('812487973', new Siren)->passes())->toBeTrue()
        ->and(check('812 487 973', new Siren)->passes())->toBeTrue()
        ->and(check('81248797300040', new Siret)->passes())->toBeTrue();
});

it('fails invalid values without throwing', function (mixed $value) {
    expect(check($value, new Siren)->fails())->toBeTrue()
        ->and(check($value, new Siret)->fails())->toBeTrue();
})->with(['abc', '123', [['x']], 1.5]);

// Laravel does not run rule objects on empty values. Use "required" to reject them.
it('skips empty values, and fails them when the field is required', function () {
    expect(Validator::make(['n' => null], ['n' => ['nullable', new Siren]])->passes())->toBeTrue()
        ->and(Validator::make(['n' => ''], ['n' => [new Siren]])->passes())->toBeTrue()
        ->and(Validator::make(['n' => ''], ['n' => ['required', new Siren]])->fails())->toBeTrue();
});

it('uses the english message by default and the french one when the locale is fr', function () {
    expect(check('123', new Siren)->errors()->first('n'))->toContain('SIREN');

    App::setLocale('fr');

    expect(check('123', new Siren)->errors()->first('n'))->toContain('SIREN')
        ->and(check('123', new Siren)->errors()->first('n'))->toContain('doit');
});

it('accepts an existing company with EntrepriseExiste', function () {
    Http::fake(['recherche-entreprises.api.gouv.fr/*' => Http::response(loadFixture('entreprises_siren.json'))]);

    expect(check('812487973', new EntrepriseExiste)->passes())->toBeTrue();
});

it('rejects an unknown company with EntrepriseExiste', function () {
    Http::fake(['recherche-entreprises.api.gouv.fr/*' => Http::response(loadFixture('entreprises_empty.json'))]);

    expect(check('812487973', new EntrepriseExiste)->fails())->toBeTrue();
});

it('rejects a malformed number with EntrepriseExiste without calling the API', function () {
    Http::fake();

    expect(check('12', new EntrepriseExiste)->fails())->toBeTrue();
    Http::assertNothingSent();
});

it('fails with a dedicated message when the API is unavailable', function () {
    Http::fake(['recherche-entreprises.api.gouv.fr/*' => Http::response('{}', 503)]);

    $validator = check('812487973', new EntrepriseExiste);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('n'))->toContain('could not be verified');
});
