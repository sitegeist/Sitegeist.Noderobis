<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Noderobis\Domain\Modification;

interface ModificationInterface
{
    public function isConfirmationRequired(): bool;

    public function getDescription(): string;

    public function apply(): void;
}
