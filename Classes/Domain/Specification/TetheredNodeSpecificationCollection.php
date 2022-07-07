<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<TetheredNodeSpecification>
 */
#[Flow\Proxy(false)]
class TetheredNodeSpecificationCollection implements \IteratorAggregate
{
    /**
     * @var array<TetheredNodeSpecification>
     */
    protected array $tetheredNodes;

    public function __construct(TetheredNodeSpecification ...$tetheredNodes)
    {
        $this->tetheredNodes = $tetheredNodes;
    }

    public function isEmpty(): bool
    {
        return empty($this->tetheredNodes);
    }

    /**
     * @return \ArrayIterator<int, TetheredNodeSpecification>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->tetheredNodes);
    }
}
