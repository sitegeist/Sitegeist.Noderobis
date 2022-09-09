<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

enum BaseType: string
{
    case Document = "Document";
    case Content = "Content";
    case Mixin = "Mixin";

    public static function fromString($value)
    {
        return self::from($value);
    }
}
