<?php

/*
 * This file is part of the Sitegeist.Nodemerobis package.
 */


declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Command;

use Flowpack\SiteKickstarter\Domain\Generator\Fusion\InheritedFusionRendererGenerator;
use Flowpack\SiteKickstarter\Domain\Generator\GeneratorInterface;
use Flowpack\SiteKickstarter\Domain\Generator\NodeType\NodetypeConfigurationGenerator;
use Flowpack\SiteKickstarter\Domain\Modification\FileContentModification;
use Flowpack\SiteKickstarter\Domain\Modification\SettingModification;
use Flowpack\SiteKickstarter\Domain\Modification\ModificationIterface;
use Flowpack\SiteKickstarter\Domain\Specification\NodeTypeSpecificationFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Flowpack\SiteKickstarter\Domain\Generator\Fusion\ContentFusionRendererGenerator;
use Flowpack\SiteKickstarter\Domain\Generator\Fusion\DocumentFusionRendererGenerator;
use Flowpack\SiteKickstarter\Domain\Modification\ModificationCollection;

/**
 * @Flow\Scope("singleton")
 */
class NodeTypeCommandController extends CommandController
{

    /**
     * @var PackageManager
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var string|null
     * @Flow\InjectConfiguration(path="defaultPackageKey")
     */
    protected $defaultPackageKey;

    /**
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $super SuperTypes
     * @param array $child ChildNodes
     * @param array $prop Node properties
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartContentCommand(string $name, ?string $packageKey = null, array $super = [], array $child = [], array $prop = []): void
    {
        $packageKey = $this->determinePackageKey($packageKey);

        \Neos\Flow\var_dump(
            [
                $packageKey,
                $name,
                $super,
                $child,
                $prop
            ]
        );
    }

    protected function determinePackageKey(?string $packageKey = null): string
    {
        if ($packageKey) {
            if ($this->packageManager->getPackage($packageKey)) {
                return $packageKey;
            } else {
                $this->outputLine('Package %s not found', $packageKey);
                $this->quit(1);
            }
        }
        if ($this->defaultPackageKey) {
            if ($this->packageManager->getPackage($this->defaultPackageKey)) {
                return $this->defaultPackageKey;
            } else {
                $this->outputLine('Default Package %s not found', $this->defaultPackageKey);
                $this->quit(1);
            }
        }
        $this->outputLine('No packageKey or default specified');
        $this->quit(1);
    }

}
