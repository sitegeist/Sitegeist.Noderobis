<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Sitegeist\Noderobis\Domain\Generator\CreateFusionRendererModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator;
use Sitegeist\Noderobis\Domain\Modification\ModificationCollection;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;

/**
 */
abstract class AbstractCommandController extends CommandController
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
     * @var NodeTypeGenerator
     * @Flow\Inject
     */
    protected $nodeTypeGenerator;

    /**
     * @var CreateNodeTypeYamlFileModificationGenerator
     * @Flow\Inject
     */
    protected $createNodeTypeYamlFileModificationGenerator;

    /**
     * @var CreateFusionRendererModificationGenerator
     * @Flow\Inject
     */
    protected $createFusionRendererModificationGenerator;

    /**
     * @param NodeTypeSpecification $nodeTypeSpecification
     * @param FlowPackageInterface $package
     * @param bool $yes
     * @return void
     */
    protected function generateNodeTypeFromSpecification(NodeTypeSpecification $nodeTypeSpecification, FlowPackageInterface $package, bool $yes = false): void
    {
        $this->outputLine();
        $this->outputLine($nodeTypeSpecification->__toString());

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $this->applyModification(
            $yes,
            $this->createNodeTypeYamlFileModificationGenerator->generateModification($package, $nodeType),
            $this->createFusionRendererModificationGenerator->generateModification($package, $nodeType),
        );
    }

    protected function applyModification(bool $yes, ModificationInterface ...$modifications): void
    {
        $collection = new ModificationCollection(... $modifications);

        $this->outputLine();
        $this->outputLine("The given specification will result in the following modifications");

        $this->outputLine();
        $this->outputLine($collection->getDescription());

        if (!$yes && $collection->isConfirmationRequired()) {
            $this->outputLine();
            $this->outputLine("Confirmation is required.");
            if ($this->output->askConfirmation("Shall we proceed (Y/n)? ") == false) {
                $this->quit(1);
            }
        }

        $collection->apply();
    }

    protected function determinePackage(?string $packageKey = null): FlowPackageInterface
    {
        if ($packageKey !== null) {
            try {
                $package = $this->packageManager->getPackage($packageKey);
                if ($package instanceof FlowPackageInterface) {
                    return $package;
                }
                $this->outputLine('Package %s is no Flow-Package', [$packageKey]);
                $this->quit(1);
                die();
            } catch (UnknownPackageException) {
                $this->outputLine('Package %s not found', [$packageKey]);
                $this->quit(1);
                die();
            }
        }

        if ($this->defaultPackageKey !== null) {
            try {
                $package = $this->packageManager->getPackage($this->defaultPackageKey);
                if ($package instanceof FlowPackageInterface) {
                    return $package;
                }
                $this->outputLine('Package %s is no Flow-Package', [$packageKey]);
                $this->quit(1);
                die();
            } catch (UnknownPackageException) {
                $this->outputLine('Default Package %s not found', [$this->defaultPackageKey]);
                $this->quit(1);
                die();
            }
        }
        $this->outputLine('No packageKey or default specified');
        $this->quit(1);
        die();
    }
}
