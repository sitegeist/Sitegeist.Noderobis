<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

class NodeTypeNameSpecificationFactory
{
    protected NodeTypeManager $nodeTypeManager;

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $crRegistry): void
    {
        $this->nodeTypeManager = $crRegistry->get(ContentRepositoryId::fromString('default'))->getNodeTypeManager();
    }

    public function generateNodeTypeNameSpecificationFromCliInput(string $name): NodeTypeNameSpecification
    {
        /**
         * @todo add more sophistication here
         */
        return NodeTypeNameSpecification::fromString($name);
    }

    /**
     * @return array<int, string>
     */
    public function getExistingNodeTypeNames(): array
    {
        $options = [];

        foreach ($this->nodeTypeManager->getNodeTypes(false) as $nodeType) {
            $options[] = $nodeType->getName();
        }
        return $options;
    }

    /**
     * @return array<int, string>
     */
    public function getExistingMixinNodeTypeNames(): array
    {
        $options = [];

        foreach ($this->nodeTypeManager->getNodeTypes(true) as $nodeType) {
            if ($nodeType->isAbstract() && strpos($nodeType->getName(), 'Mixin') !== false) {
                $options[] = $nodeType->getName();
            }
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public function getExistingConstraintNodeTypeNames(): array
    {
        $options = [];

        foreach ($this->nodeTypeManager->getNodeTypes(true) as $nodeType) {
            if ($nodeType->isAbstract() && strpos($nodeType->getName(), 'Constraint') !== false) {
                $options[] = $nodeType->getName();
            }
        }

        return $options;
    }
}
