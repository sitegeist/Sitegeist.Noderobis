<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class TetheredNodeSpecification
{
    public function __construct(
        public readonly NodeName $name,
        public readonly NodeTypeName $type
    ) {
    }
}
