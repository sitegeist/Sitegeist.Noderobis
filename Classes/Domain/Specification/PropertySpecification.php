<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PropertySpecification
{
    public function __construct(
        public readonly PropertyName $name,
        public readonly PropertyType|PropertyPresetName $typeOrPreset,
        public readonly ?PropertyLabel $label = null,
        public readonly ?PropertyDescription $description = null,
        public readonly ?PropertyGroupName $group = null,
        public readonly bool $isRequired = false
    ) {
    }
}
