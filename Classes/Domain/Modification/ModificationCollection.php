<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Nodemerobis\Domain\Modification;

/**
 * Class ModificationCollection
 * @package Flowpack\SiteKickstarter\Domain\Modification
 * @implements \IteratorAggregate<int, ModificationInterface>
 */
class ModificationCollection implements ModificationInterface, \IteratorAggregate
{
    /**
     * @var array<int, ModificationInterface>
     */
    protected array $modifications;

    public function __construct(ModificationInterface ...$modifications)
    {
        $this->modifications = [];
        foreach ($modifications as $modification) {
            if ($modification instanceof ModificationCollection) {
                foreach (iterator_to_array($modification) as $partialModification) {
                    $this->modifications[] = $partialModification;
                }
            } else {
                $this->modifications[] = $modification;
            }
        }
    }

    public function isConfirmationRequired(): bool
    {
        foreach ($this->modifications as $modification) {
            if ($modification->isConfirmationRequired()) {
                return true;
            }
        }
        return false;
    }

    public function getDescription(): string
    {
        $abstracts = array_map(
            function (ModificationInterface $modification) {
                return $modification->getDescription();
            },
            $this->modifications
        );
        return ' - ' . implode(PHP_EOL . ' - ', $abstracts);
    }

    public function apply(): void
    {
        foreach ($this->modifications as $modification) {
            $modification->apply();
        }
    }

    /**
     * @return \ArrayIterator<int, ModificationInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->modifications);
    }
}
