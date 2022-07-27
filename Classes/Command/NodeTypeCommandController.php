<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Command;

use Neos\ContentRepository\Migration\Filters\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Sitegeist\Noderobis\Domain\Generator\CreateFusionRendererModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator;
use Sitegeist\Noderobis\Domain\Modification\ModificationCollection;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;
use Sitegeist\Noderobis\Domain\Specification\IconNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyDescriptionSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyGroupNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyPresetNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\PropertyTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationFactory;

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
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $childNode (optional)
     * @param array $property (optional)
     * @param bool $abstract
     * @param bool $yes
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartDocumentCommand(string $name, ?string $packageKey = null, array $childNode = [], array $property = [], bool $abstract = false, bool $yes = false): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Document.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Document']),
            $this->propertySpecificationFactory->generatePropertySpecificationCollectionFromCliInputArray($property),
            $this->tetheredNodeSpecificationFactory->generateTetheredNodeSpecificationCollectionFromCliInputArray($childNode),
            $abstract
        );

        if (!$yes) {
            $nodeTypeSpecification = $this->refineNodeTypeSpecification($nodeTypeSpecification);
        }
        $this->generateNodeTypeFromSpecification($nodeTypeSpecification, $package, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $childNode (optional)
     * @param array $property (optional)
     * @param bool $abstract
     * @param bool $yes
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function kickstartContentCommand(string $name, ?string $packageKey = null, array $childNode = [], array $property = [], bool $abstract = false, bool $yes = false): void
    {
        $package = $this->determinePackage($packageKey);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':Content.' . $name),
            NodeTypeNameSpecificationCollection::fromStringArray(['Neos.Neos:Content']),
            $this->propertySpecificationFactory->generatePropertySpecificationCollectionFromCliInputArray($property),
            $this->tetheredNodeSpecificationFactory->generateTetheredNodeSpecificationCollectionFromCliInputArray($childNode),
            $abstract
        );

        if (!$yes) {
            $nodeTypeSpecification = $this->refineNodeTypeSpecification($nodeTypeSpecification);
        }
        $this->generateNodeTypeFromSpecification($nodeTypeSpecification, $package, $yes);
    }

    protected function refineNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $this->outputLine();
        $this->outputLine((string)$nodeTypeSpecification);
        $this->outputLine();

        $choices = [
            "add Label",
            "add Icon",
            "add Property",
            "add ChildNode",
            "add SuperType"
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
                "FINISH and generate files"
            ],
            "generate NodeType"
        );

        switch ($choice) {
            case "add Label":
                $nodeTypeSpecification = $this->addLabelToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
            case "add Icon":
                $nodeTypeSpecification = $this->addIconToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineNodeTypeSpecification($nodeTypeSpecification);
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
            case "FINISH and generate files":
                return $nodeTypeSpecification;
            default:
                $this->outputLine("Unkonwn option %s", [$choice]);
                $this->quit(1);
                die(); #just to make phpstan happy
        }
    }

    protected function addLabelToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $text = $this->output->ask("Label: ");
        if (is_string($text)) {
            $label = new NodeTypeLabelSpecification((string)$text);
            return $nodeTypeSpecification->withLabel($label);
        } else {
            return $nodeTypeSpecification;
        }
    }

    protected function addIconToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->ask("Icob: ");
        if (is_string($name)) {
            $icon = new IconNameSpecification($name);
            return $nodeTypeSpecification->withIcon($icon);
        } else {
            return $nodeTypeSpecification;
        }
    }

    protected function addSuperTypeToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->select("SuperType: ", $this->nodeTypeNameSpecificationFactory->getExistingNodeTypeNames());
        if (is_string($name)) {
            $nodeTypeName = NodeTypeNameSpecification::fromString($name);
            return $nodeTypeSpecification->withSuperType($nodeTypeName);
        } else {
            return $nodeTypeSpecification;
        }
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
}
