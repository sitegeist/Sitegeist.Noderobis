<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Noderobis\Domain\Modification\WriteFileModification;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Symfony\Component\Yaml\Yaml;

class CreateNodeTypeYamlFileModificationGenerator implements ModificationGeneratorInterface
{
    public function generateModification(FlowPackageInterface $package, NodeType $nodeType): ModificationInterface
    {
        $nodeTypeComment = <<<EOT
            #
            # Definition of NodeType {$nodeType->getName()}
            #
            # @see https://docs.neos.io/cms/manual/content-repository/nodetype-definition
            # @see https://docs.neos.io/cms/manual/content-repository/nodetype-properties
            #
            EOT;

        $configuration = [];
        foreach ($nodeType->getDeclaredSuperTypes() as $declaredSuperType) {
            $configuration['superTypes'][$declaredSuperType->getName()] = true;
        }
        if ($nodeType->isAbstract()) {
            $configuration['abstract'] = true;
        }
        if ($nodeType->isFinal()) {
            $configuration['final'] = true;
        }

        $configuration = array_merge($configuration, $nodeType->getLocalConfiguration());

        $nodeTypeNameSpecification = NodeTypeNameSpecification::fromString($nodeType->getName());
        $filePath = $package->getPackagePath()  . 'NodeTypes/' . implode('/', $nodeTypeNameSpecification->getLocalNameParts()) . '/' . $nodeTypeNameSpecification->getNickname() . '.yaml';
        return new WriteFileModification(
            $filePath,
            $nodeTypeComment . chr(10) . Yaml::dump([ $nodeType->getName() => $configuration], 99, 2)
        );
    }
}
