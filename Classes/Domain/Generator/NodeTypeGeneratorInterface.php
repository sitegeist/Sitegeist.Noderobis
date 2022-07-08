<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeSpecification;

interface NodeTypeGeneratorInterface
{
    public function generateNodeType(NodeTypeSpecification $nodeTypeSpecification): NodeType;
}
