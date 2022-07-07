<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<int, PropertySpecification>
 */
#[Flow\Proxy(false)]
class PropertySpecificationCollection implements \IteratorAggregate
{
    /**
     * @var array<int, PropertySpecification>
     */
    protected array $nodeProperties;

    public function __construct(PropertySpecification ...$nodeProperties)
    {
        $this->nodeProperties = $nodeProperties;
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
}
