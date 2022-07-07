<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Flowpack\SiteKickstarter\Domain\Modification\ModificationIterface;
use Flowpack\SiteKickstarter\Domain\Specification\NodeTypeSpecification;

interface GeneratorInterface
{
    public function generate(NodeTypeSpecification $nodeType): ModificationIterface;
}
