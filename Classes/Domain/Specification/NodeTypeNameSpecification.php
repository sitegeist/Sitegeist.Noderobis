<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class NodeTypeNameSpecification
{
    public function __construct(
        public readonly string $packageKey,
        public readonly string $localName
    ) {
    }

    public static function fromString(string $string): static
    {
        list ($packageKey, $localName) = explode(':', $string);

        return new static(
            $packageKey,
            $localName
        );
    }

    public function getFullName(): string
    {
        return $this->packageKey . ':' . $this->localName;
    }

    /**
     * @return string[]
     */
    public function getLocalNameParts(): array
    {
        return explode('.', $this->localName);
    }

    public function getNickname(): string
    {
        $localNameParts = $this->getLocalNameParts();

        return $localNameParts[array_key_last($localNameParts)];
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
