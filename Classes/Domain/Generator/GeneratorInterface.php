<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Sitegeist\Nodemerobis\Domain\Modification\ModificationInterface;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeSpecification;

interface GeneratorInterface
{
    public function generate(NodeTypeSpecification $nodeType): ModificationInterface;
}
