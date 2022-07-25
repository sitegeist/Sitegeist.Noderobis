<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Noderobis\Domain\Modification;

use Neos\Utility\Files;

class DoNothingModification implements ModificationInterface
{
    public function __construct()
    {
    }

    public function isConfirmationRequired(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return "nothing";
    }

    public function apply(): void
    {
    }
}
