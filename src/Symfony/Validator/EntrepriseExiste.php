<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * The value must be the SIREN of a company known by the Recherche d'entreprises API.
 * Calls the API, so validation depends on an external service; a service failure rejects the value.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class EntrepriseExiste extends Constraint
{
    public string $message = 'This value does not match any known company.';

    public string $unavailableMessage = 'This value could not be verified because the company service is unavailable.';

    public string $formatMessage = 'This value must be a valid SIREN number (9 digits).';

    /** @param list<string>|null $groups */
    public function __construct(
        ?string $message = null,
        ?string $unavailableMessage = null,
        ?string $formatMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->unavailableMessage = $unavailableMessage ?? $this->unavailableMessage;
        $this->formatMessage = $formatMessage ?? $this->formatMessage;
    }
}
