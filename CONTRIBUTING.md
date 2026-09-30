# Contributing

Thank you for helping. Small and clear changes are easy to review.

## Install

```bash
git clone https://github.com/kaveraa/api-gouv-publique-fr.git
cd api-gouv-publique-fr
composer install
```

## Run the checks

```bash
composer test      # run the tests (Pest)
composer lint      # check the code style (Pint)
composer analyse   # run static analysis (PHPStan)
composer format    # fix the code style
```

The tests in `tests/Unit` do not need Laravel. The tests in `tests/Laravel` use Orchestra Testbench.

The live tests call the real APIs. They are not part of the default run:

```bash
vendor/bin/pest tests/Live
```

## Style of commits and pull requests

- Write in simple English.
- Keep the commit message short. Start with a verb: "Add", "Fix", "Update".
- One change per pull request.
- Add or update tests for every change.
- Update the documentation in `docs/en` and `docs/fr` when the public API changes.
- Add a line to `CHANGELOG.md` under "Unreleased".

## Record a new fixture

Fixtures are real API answers saved in `tests/fixtures`. To add one:

1. Call the API and save the JSON body. For example:

   ```bash
   curl "https://recherche-entreprises.api.gouv.fr/search?q=812487973&per_page=1" -o tests/fixtures/entreprises_new.json
   ```

2. Remove any personal data that you do not need.
3. Load it in a test with `loadFixture('entreprises_new.json')` and a `FakeTransport`.

## Code of conduct

Be kind and be clear. Give people the benefit of the doubt.
