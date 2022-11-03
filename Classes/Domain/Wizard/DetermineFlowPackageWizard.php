<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Wizard;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;

class DetermineFlowPackageWizard
{
    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\InjectConfiguration("defaultPackageKey")]
    protected string|null $defaultPackageKey;

    public function determineFlowPackage(?string $packageKey = null): FlowPackageInterface
    {
        if ($packageKey !== null) {
            try {
                $package = $this->packageManager->getPackage($packageKey);
                if ($package instanceof FlowPackageInterface) {
                    return $package;
                }
                throw new \InvalidArgumentException(sprintf('Package %s is no Flow-Package', $packageKey));
            } catch (UnknownPackageException) {
                throw new \InvalidArgumentException(sprintf('Package %s not found', $packageKey));
            }
        }

        if ($this->defaultPackageKey !== null) {
            try {
                $package = $this->packageManager->getPackage($this->defaultPackageKey);
                if ($package instanceof FlowPackageInterface) {
                    return $package;
                }
                throw new \InvalidArgumentException(sprintf('Package %s is no Flow-Package', $packageKey));
            } catch (UnknownPackageException) {
                throw new \InvalidArgumentException(sprintf('Default Package %s not found', $this->defaultPackageKey));
            }
        }
        throw new \InvalidArgumentException('No packageKey or default specified');
    }
}
