<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;

interface ModificationGeneratorInterface
{
    public function generateModification(FlowPackageInterface $package, NodeType $nodeType): ModificationInterface;
}
