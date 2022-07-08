<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Mvc\Routing\Exception\InvalidControllerException;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeSpecification;

class NodeTypeGenerator implements NodeTypeGeneratorInterface
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    public function generateNodeType(NodeTypeSpecification $nodeTypeSpecification): NodeType
    {
        $localConfiguration = [];
        $superTypes = [];
        foreach ($nodeTypeSpecification->superTypes as $superTypeSpecification) {
            /** @var NodeType|null $superType  */
            $superType = $this->nodeTypeManager->getNodeType($superTypeSpecification->getFullName());
            if ($superType instanceof NodeType) {
                $superTypes[] = $superType;
                $localConfiguration['superTypes'][$superTypeSpecification->getFullName()] = true;
            } else {
                throw new \InvalidArgumentException(sprintf('Unknown supertype %s', $superTypeSpecification->getFullName()));
            }
        }

        if ($nodeTypeSpecification->abstract) {
            $localConfiguration['abstract'] = true;
        }

        $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);
        return $nodeType;
    }
}
