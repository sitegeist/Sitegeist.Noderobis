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
        $filePath = $package->getPackagePath() . 'Resources/Private/Fusion/Root.fusion';
        return new AddToFileModification($filePath, 'include: nodetypes://' . $package->getPackageKey() . '/**/*.fusion', true);
    }
}
