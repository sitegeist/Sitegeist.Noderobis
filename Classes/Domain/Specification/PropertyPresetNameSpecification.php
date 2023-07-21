<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class PropertyPresetNameSpecification
{
    public function __construct(
        public readonly string $presetName
    ) {
    }

    public function __toString(): string
    {
        return 'preset.' . $this->presetName;
    }
}
