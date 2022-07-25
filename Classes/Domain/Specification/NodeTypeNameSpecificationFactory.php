<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

class NodeTypeNameSpecificationFactory
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

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
}
