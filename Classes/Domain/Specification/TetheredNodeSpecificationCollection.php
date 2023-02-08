<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

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

    public function withTetheredNode(TetheredNodeSpecification $tetheredNode): self
    {
        return new self(...[...$this->tetheredNodes, $tetheredNode]);
    }

    public function withoutTetheredNode(TetheredNodeSpecification $tetheredNode): self
    {
        $tetheredNodesFiltered = array_filter(
            $this->tetheredNodes,
            function (TetheredNodeSpecification $spec) use ($tetheredNode) {
                return $spec !== $tetheredNode;
            }
        );
        return new self(...$tetheredNodesFiltered);
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

    public function __toString(): string
    {
        return implode(
            ', ',
            array_map(
                function (TetheredNodeSpecification $tetheredNode) {
                    return $tetheredNode->__toString();
                },
                $this->tetheredNodes
            )
        );
    }
}
