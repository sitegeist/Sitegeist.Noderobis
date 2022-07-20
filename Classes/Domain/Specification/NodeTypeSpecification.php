<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class NodeTypeSpecification
{
    public function __construct(
        public readonly NodeTypeNameSpecification $name,
        public readonly NodeTypeNameSpecificationCollection $superTypes,
        public readonly PropertySpecificationCollection $nodeProperties,
        public readonly TetheredNodeSpecificationCollection $tetheredNodes,
        public readonly bool $abstract,
        public readonly ?NodeTypeLabelSpecification $label = null
    ) {
    }

    public function withSuperType(NodeTypeNameSpecification $nodeTypeName): self
    {
        return new self(
            $this->name,
            $this->superTypes->withNodeTypeName($nodeTypeName),
            $this->nodeProperties,
            $this->tetheredNodes,
            $this->abstract,
            $this->label
        );
    }

    public function withProperty(PropertySpecification $property): self
    {
        return new self(
            $this->name,
            $this->superTypes,
            $this->nodeProperties->withProperty($property),
            $this->tetheredNodes,
            $this->abstract,
            $this->label
        );
    }

    public function withTeheredNode(TetheredNodeSpecification $tetheredNode): self
    {
        return new self(
            $this->name,
            $this->superTypes,
            $this->nodeProperties,
            $this->tetheredNodes->withTetheredNode($tetheredNode),
            $this->abstract,
            $this->label
        );
    }

    public function withLabel(NodeTypeLabelSpecification $label): self
    {
        return new self(
            $this->name,
            $this->superTypes,
            $this->nodeProperties,
            $this->tetheredNodes,
            $this->abstract,
            $label
        );
    }

    public function withAbstract(bool $abstract): self
    {
        return new self(
            $this->name,
            $this->superTypes,
            $this->nodeProperties,
            $this->tetheredNodes,
            $abstract,
            $this->label
        );
    }

    public function __toString(): string
    {
        $lines = [];
        $lines[] = $this->name->__toString();
        if ($this->abstract) {
            $lines[] = '  abstract';
        }
        if (!$this->superTypes->isEmpty()) {
            $lines[] = "  SuperTypes: " . $this->superTypes->__toString();
        }
        if (!$this->tetheredNodes->isEmpty()) {
            $lines[] = "  ChildNodes: " . $this->tetheredNodes->__toString();
        }
        if (!$this->nodeProperties->isEmpty()) {
            $lines[] = "  Properties: " . $this->nodeProperties->__toString();
        }
        return implode(PHP_EOL, $lines);
    }
}
