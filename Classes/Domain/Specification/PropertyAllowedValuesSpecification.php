<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PropertyAllowedValuesSpecification
{
    /**
     * @var array<int|string>
     */
    public readonly array $allowedValues;

    public function __construct(
        int|string ...$allowedValues
    ) {
        $this->allowedValues = $allowedValues;
    }

    public function __toString(): string
    {
        return implode(',', $this->allowedValues);
    }
}
