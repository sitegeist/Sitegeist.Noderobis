<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

#[Flow\Proxy(false)]
class OptionsSpecification
{
    /**
     * @param array<mixed>|null $options
     */
    public function __construct(
        public readonly ?array $options
    ) {
    }

    public function withOption(string $path, mixed $value): static
    {
        /**
         * @var array<mixed> $newOptions
         */
        $newOptions = Arrays::setValueByPath($this->options ?? [], $path, $value);
        return new static(
            $newOptions
        );
    }
}
