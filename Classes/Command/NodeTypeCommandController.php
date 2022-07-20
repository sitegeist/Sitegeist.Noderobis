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
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertySpecificationFactory;
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
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodeSpecificationFactory;

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
     * @var PropertySpecificationFactory
     * @Flow\Inject
     */
    protected $propertySpecificationFactory;

    /**
     * @var TetheredNodeSpecificationFactory
     * @Flow\Inject
     */
    protected $tetheredNodeSpecificationFactory;

    /**
     * @var NodeTypeNameSpecificationFactory
     * @Flow\Inject
     */
    protected $nodeTypeNameSpecificationFactory;

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
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartDocumentCommand(string $name, ?string $packageKey = null): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Document.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Document']),
            new PropertySpecificationCollection(),
            new TetheredNodeSpecificationCollection(),
            false
        );

        $nodeTypeSpecification = $this->refineNodeTypeSpecification($nodeTypeSpecification);
        $this->generateNodeTypeFromSpecification($nodeTypeSpecification, $package);
    }

    /**
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartContentCommand(string $name, ?string $packageKey = null): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Content.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Content']),
            new PropertySpecificationCollection(),
            new TetheredNodeSpecificationCollection(),
            false
        );

        $nodeTypeSpecification = $this->refineNodeTypeSpecification($nodeTypeSpecification);
        $this->generateNodeTypeFromSpecification($nodeTypeSpecification, $package);
    }

    protected function refineNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $this->outputLine();
        $this->outputLine((string)$nodeTypeSpecification);
        $this->outputLine();

        $choices = [
            "add Property",
            "add ChildNode",
            "add SuperType",
        ];

        if ($nodeTypeSpecification->abstract) {
            $choices[] = 'make Non-Abstract';
        } else {
            $choices[] = 'make Abstract';
        }

        $choice = $this->output->select(
            "What is next?",
            [
                ...$choices,
                "generate NodeType",
                "quit"
            ],
            "generate NodeType"
        );

        switch ($choice) {
            case "add Property":
                $nodeTypeSpecification = $this->addPropertyToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "add ChildNode":
                $nodeTypeSpecification = $this->addTetheredNodeToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "add SuperType":
                $nodeTypeSpecification = $this->addSuperTypeToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "make Abstract":
                $nodeTypeSpecification = $nodeTypeSpecification->withAbstract(true);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "make Non-Abstract":
                $nodeTypeSpecification = $nodeTypeSpecification->withAbstract(false);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "generate NodeType":
                return $nodeTypeSpecification;
            case "quit":
                $this->quit(0);
                die(); #just to make phpstan happy
            default:
                $this->outputLine("Unkonwn option %s", [$choice]);
                $this->quit(1);
                die(); #just to make phpstan happy
        }
    }

    protected function addSuperTypeToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->select("SuperType: ", $this->nodeTypeNameSpecificationFactory->getExistingNodeTypeNames());

        $nodeTypeName = NodeTypeNameSpecification::fromString($name);

        return $nodeTypeSpecification->withSuperType($nodeTypeName);
    }


    protected function addTetheredNodeToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->ask("ChildNode name: ");
        $type = $this->output->select("ChildNode type: ", $this->tetheredNodeSpecificationFactory->getTypeConfiguration());

        if (!is_string($name) || !is_string($type)) {
            return $nodeTypeSpecification;
        }

        $tetheredNode = $this->tetheredNodeSpecificationFactory->generateTetheredNodeSpecificationFromCliInput(
            trim($name),
            $type
        );

        return $nodeTypeSpecification->withTeheredNode($tetheredNode);
    }

    protected function addPropertyToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->ask("Property name: ");
        $type = $this->output->select("Property type: ", $this->propertySpecificationFactory->getTypeConfiguration());

        if (!is_string($name) || !is_string($type)) {
            return $nodeTypeSpecification;
        }

        $propertySpecification = $this->propertySpecificationFactory->generatePropertySpecificationFromCliInput(
            trim($name),
            $type
        );

        return $nodeTypeSpecification->withProperty($propertySpecification);
    }

    protected function applyModification(ModificationInterface ...$modifications): void
    {
        $collection = new ModificationCollection(... $modifications);

        if ($collection->isConfirmationRequired()) {
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

    /**
     * @param NodeTypeSpecification $nodeTypeSpecification
     * @param FlowPackageInterface $package
     * @return void
     */
    protected function generateNodeTypeFromSpecification(NodeTypeSpecification $nodeTypeSpecification, FlowPackageInterface $package): void
    {
        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $this->applyModification(
            $this->createNodeTypeYamlFileModificationGenerator->generateModification($package, $nodeType),
            $this->createFusionRendererModificationGenerator->generateModification($package, $nodeType)
        );
    }
}
