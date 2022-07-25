<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<PropertySpecification>
 */
#[Flow\Proxy(false)]
class PropertySpecificationCollection implements \IteratorAggregate
{
    /**
     * @var array<PropertySpecification>
     */
    protected array $nodeProperties;

    public function __construct(PropertySpecification ...$nodeProperties)
    {
        $this->nodeProperties = $nodeProperties;
    }

    public function withProperty(PropertySpecification $property): self
    {
        return new self(...[...$this->nodeProperties, $property]);
    }

    public function isEmpty(): bool
    {
        return empty($this->nodeProperties);
    }

    /**
     * @return \ArrayIterator<int, PropertySpecification>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeProperties);
    }

    public function __toString(): string
    {
        return implode(
            ', ',
            array_map(
                function (PropertySpecification $nodeProperty) {
                        return (string)$nodeProperty;
                },
                $this->nodeProperties
            )
        );
    }
}
