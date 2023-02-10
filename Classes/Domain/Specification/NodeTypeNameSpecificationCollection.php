<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Flowpack\SiteKickstarter\Domain\Specification\NameSpecification;
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

    public function withNodeTypeName(NodeTypeNameSpecification $nameSpecification): self
    {
        return new self(...[...$this->nameSpecifications, $nameSpecification]);
    }

    public function withoutNodeTypeName(NodeTypeNameSpecification $nameSpecification): self
    {
        $nameSpecificationsFiltered = array_filter(
            $this->nameSpecifications,
            function (NodeTypeNameSpecification $spec) use ($nameSpecification) {
                return $spec !== $nameSpecification;
            }
        );
        return new self(...$nameSpecificationsFiltered);
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

    public function __toString(): string
    {
        return implode(
            ', ',
            array_map(
                function (NodeTypeNameSpecification $nameSpecification) {
                    return (string)$nameSpecification;
                },
                $this->nameSpecifications
            )
        );
    }
}
