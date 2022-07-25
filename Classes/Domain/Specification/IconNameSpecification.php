<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class IconNameSpecification
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
