<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Modification;

interface ModificationInterface
{
    public function isConfirmationRequired(): bool;

    public function getDescription(): string;

    public function apply(): void;
}
