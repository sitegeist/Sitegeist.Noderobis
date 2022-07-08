<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Nodemerobis\Domain\Modification\ModificationInterface;

interface ModificationGeneratorInterface
{
    public function generateModification(FlowPackageInterface $package, NodeType $nodeType): ModificationInterface;
}
