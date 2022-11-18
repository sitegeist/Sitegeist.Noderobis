<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Noderobis\Domain\Modification\AddToFileModification;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;

class IncludeFusionFromNodeTypesModificationGenerator implements ModificationGeneratorInterface
{
    public function generateModification(FlowPackageInterface $package, NodeType $nodeType): ModificationInterface
    {
        $rootFusionPath = $package->getPackagePath() . 'Resources/Private/Fusion/Root.fusion';
        $packageKey = $package->getPackageKey();
        if (class_exists('\Neos\Neos\ResourceManagement\NodeTypesStreamWrapper')) {
            $includeFusionFromNodeTypes = <<<EOF
            include: nodetypes://{$packageKey}/**/*.fusion
            EOF;
        } else {
            $includeFusionFromNodeTypes = <<<EOF
            // @todo after update to Neos 8.2 update to `include: nodetypes://{$packageKey}/**/*.fusion`
            include: resource://{$packageKey}/../NodeTypes/**/*.fusion
            EOF;
        }
        return new AddToFileModification($rootFusionPath, $includeFusionFromNodeTypes, true);

    }
}
