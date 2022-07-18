<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class TetheredNodeSpecification
{
    public function __construct(
        public readonly TetheredNodeNameSpecification $name,
        public readonly NodeTypeNameSpecification|TetheredNodePresetNameSpecification $typeOrPreset
    ) {
    }

    public function __toString(): string
    {
        return $this->name->name . '(' . $this->typeOrPreset . ')';
    }
}
