<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;

interface NodeTypeGeneratorInterface
{
    public function generateNodeType(NodeTypeSpecification $nodeTypeSpecification): NodeType;
}
