<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class NodeTypeSpecification
{
    public function __construct(
        public readonly NodeTypeNameSpecification $name,
        public readonly NodeTypeNameSpecificationCollection $superTypes,
        public readonly PropertySpecificationCollection $nodeProperties,
        public readonly TetheredNodeSpecificationCollection $tetheredNodes,
        public readonly bool $abstract,
        public readonly ?NodeTypeLabelSpecification $label = null
    ) {
    }
}
