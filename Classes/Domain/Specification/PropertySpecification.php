<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PropertySpecification
{
    public function __construct(
        public readonly PropertyNameSpecification $name,
        public readonly PropertyTypeSpecification|PropertyPresetNameSpecification $typeOrPreset,
        public readonly ?PropertyAllowedValuesSpecification $allowedValues = null,
        public readonly ?PropertyLabelSpecification $label = null,
        public readonly ?PropertyDescriptionSpecification $description = null,
        public readonly ?PropertyGroupNameSpecification $group = null,
        public readonly bool $isRequired = false
    ) {
    }

    public function __toString(): string
    {
        $string = $this->name->name . '(' . $this->typeOrPreset . ')';
        if ($this->allowedValues) {
            $string .= ' - ' . $this->allowedValues;
        }
        return $string;
    }
}
