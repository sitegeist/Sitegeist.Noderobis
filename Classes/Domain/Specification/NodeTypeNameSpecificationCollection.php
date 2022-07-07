<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<NodeTypeNameSpecification>
 */
#[Flow\Proxy(false)]
class NodeTypeNameSpecificationCollection implements \IteratorAggregate
{
    /**
     * @var array<NodeTypeNameSpecification>
     */
    protected array $nameSpecifications;

    public function __construct(NodeTypeNameSpecification ...$nameSpecifications)
    {
        $this->nameSpecifications = $nameSpecifications;
    }

    /**
     * @param string[] $names
     */
    public static function fromStringArray(array $names): static
    {
        return new static(
            ...array_map(
                function (string $name) {
                    return NodeTypeNameSpecification::fromString($name);
                },
                $names
            )
        );
    }

    /**
     * @return \ArrayIterator<int, NodeTypeNameSpecification>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nameSpecifications);
    }

    public function isEmpty(): bool
    {
        return empty($this->nameSpecifications);
    }
}
