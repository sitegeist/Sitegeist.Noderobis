<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PropertyAllowedValuesSpecification
{

    public readonly array $allowedValues;

    public function __construct(
        mixed ...$allowedValues
    ) {
        $this->allowedValues = $allowedValues;
    }

    public function __toString(): string
    {
        return implode(',', $this->allowedValues);
    }
}
