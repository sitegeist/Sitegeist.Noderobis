<?php

/*
 * This file is part of the Sitegeist.Nodemerobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Command;

use Neos\ContentRepository\Migration\Filters\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Sitegeist\Nodemerobis\Domain\Generator\CreateFusionRendererModificationGenerator;
use Sitegeist\Nodemerobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Nodemerobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator;
use Sitegeist\Nodemerobis\Domain\Modification\ModificationCollection;
use Sitegeist\Nodemerobis\Domain\Modification\ModificationInterface;
use Sitegeist\Nodemerobis\Domain\Specification\NodeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyDescriptionSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyGroupNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyLabelSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyPresetNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertySpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertySpecificationCollection;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyTypeSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodeSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodeSpecificationCollection;

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
     * @phpstan-param string[] $super
     * @phpstan-param string[] $child
     * @phpstan-param string[] $prop
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $super SuperTypes
     * @param array $child ChildNodes
     * @param array $prop Node properties
     * @param bool $force Apply all modifications without asking confirmation for overwriting
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartDocumentCommand(string $name, ?string $packageKey = null, array $super = [], array $child = [], array $prop = [], bool $force = false): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Document.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Document']),
            new PropertySpecificationCollection(
                new PropertySpecification(
                    new PropertyNameSpecification('title'),
                    new PropertyTypeSpecification('string'),
                    new PropertyLabelSpecification('Titel'),
                    new PropertyDescriptionSpecification('Der titel vons ganze'),
                    new PropertyGroupNameSpecification('dings'),
                    true
                ),
                new PropertySpecification(
                    new PropertyNameSpecification('text'),
                    new PropertyPresetNameSpecification('richtext'),
                    new PropertyLabelSpecification('Text'),
                    new PropertyDescriptionSpecification('Der text vons ganze'),
                    new PropertyGroupNameSpecification('dings'),
                    true
                ),
            ),
            new TetheredNodeSpecificationCollection(
                new TetheredNodeSpecification(
                    new NodeNameSpecification('main'),
                    NodeTypeNameSpecification::fromString('Neos.Neos:ContentCollection')
                )
            ),
            false
        );

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $this->applyModification(
            $force,
            $this->createNodeTypeYamlFileModificationGenerator->generateModification($package, $nodeType),
            $this->createFusionRendererModificationGenerator->generateModification($package, $nodeType)
        );
    }

    /**
     * @phpstan-param string[] $super
     * @phpstan-param string[] $child
     * @phpstan-param string[] $prop
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $super SuperTypes
     * @param array $child ChildNodes
     * @param array $prop Node properties
     * @param bool $force Apply all modifications without asking confirmation for overwriting
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartContentCommand(string $name, ?string $packageKey = null, array $super = [], array $child = [], array $prop = [], bool $force = false): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Content.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Content']),
            new PropertySpecificationCollection(
                new PropertySpecification(
                    new PropertyNameSpecification('title'),
                    new PropertyTypeSpecification('string'),
                    new PropertyLabelSpecification('Titel'),
                    new PropertyDescriptionSpecification('Der titel vons ganze'),
                    new PropertyGroupNameSpecification('dings'),
                    true
                ),
                new PropertySpecification(
                    new PropertyNameSpecification('text'),
                    new PropertyPresetNameSpecification('richtext'),
                    new PropertyLabelSpecification('Text'),
                    new PropertyDescriptionSpecification('Der text vons ganze'),
                    new PropertyGroupNameSpecification('dings'),
                    true
                ),
            ),
            new TetheredNodeSpecificationCollection(
                new TetheredNodeSpecification(
                    new NodeNameSpecification('main'),
                    NodeTypeNameSpecification::fromString('Neos.Neos:ContentCollection')
                )
            ),
            false
        );

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $this->applyModification(
            $force,
            $this->createNodeTypeYamlFileModificationGenerator->generateModification($package, $nodeType),
            $this->createFusionRendererModificationGenerator->generateModification($package, $nodeType)
        );
    }

    protected function applyModification(bool $force = false, ModificationInterface ...$modifications): void
    {
        $collection = new ModificationCollection(... $modifications);

        if (!$force && $collection->isConfirmationRequired()) {
            $this->outputLine();
            $this->outputLine("Confirmation is required. The command will apply following modifications:");
            $this->outputLine($collection->getDescription());
            if ($this->output->askConfirmation("Shall we proceed (Y/n)? ") == false) {
                $this->quit(1);
            }
        }

        $collection->apply();
        $this->outputLine();
        $this->outputLine("The following modifications were applied:");
        $this->outputLine($collection->getDescription());
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
