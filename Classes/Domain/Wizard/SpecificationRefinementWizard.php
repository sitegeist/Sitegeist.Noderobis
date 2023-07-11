<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Wizard;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Utility\Arrays;
use Neos\Utility\Exception\InvalidTypeException;
use Sitegeist\Noderobis\Domain\Specification\IconNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationFactory;

class SpecificationRefinementWizard
{
    #[Flow\Inject]
    protected PropertySpecificationFactory $propertySpecificationFactory;

    #[Flow\Inject]
    protected TetheredNodeSpecificationFactory $tetheredNodeSpecificationFactory;

    #[Flow\Inject]
    protected NodeTypeNameSpecificationFactory $nodeTypeNameSpecificationFactory;

    /**
     * @var string[]
     */
    protected $propertyTypesWithAllowedValues = ['integer', 'string', 'array'];

    public function __construct(
        private readonly ConsoleOutput $output
    ) {
    }

    public function refineSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $this->output->outputLine();
        $this->output->outputLine((string)$nodeTypeSpecification);
        $this->output->outputLine();

        $choices = [
            "add Label",
            "add Icon",
            "add Property",
            "add ChildNode",
            "add SuperType",
            "add Mixin",
            "add Constraint"
        ];

        if ($nodeTypeSpecification->abstract) {
            $choices[] = 'make Non-Abstract';
        } else {
            $choices[] = 'make Abstract';
        }

        if (!$nodeTypeSpecification->nodeProperties->isEmpty()) {
            $choices[] = "remove Property";
        }

        if (!$nodeTypeSpecification->tetheredNodes->isEmpty()) {
            $choices[] = "remove ChildNode";
        }

        if (!$nodeTypeSpecification->superTypes->isEmpty()) {
            $choices[] = "remove SuperType";
        }

        /**
         * @var string
         */
        $choice = $this->output->select(
            "What is next?",
            [
                "FINISH and generate files",
                ...$choices,
                "exit"
            ],
            "generate NodeType"
        );

        switch ($choice) {
            case "FINISH and generate files":
                return $nodeTypeSpecification;
            case "add Label":
                $nodeTypeSpecification = $this->addLabelToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add Icon":
                $nodeTypeSpecification = $this->addIconToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add Property":
                $nodeTypeSpecification = $this->addPropertyToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add ChildNode":
                $nodeTypeSpecification = $this->addTetheredNodeToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add SuperType":
                $nodeTypeSpecification = $this->addSuperTypeToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add Mixin":
                $nodeTypeSpecification = $this->addMixinToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "add Constraint":
                $nodeTypeSpecification = $this->addConstraintToNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "make Abstract":
                $nodeTypeSpecification = $nodeTypeSpecification->withAbstract(true);
                return $this->refineSpecification($nodeTypeSpecification);
            case "make Non-Abstract":
                $nodeTypeSpecification = $nodeTypeSpecification->withAbstract(false);
                return $this->refineSpecification($nodeTypeSpecification);
            case "remove Property":
                $nodeTypeSpecification = $this->removePropertyFromNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "remove ChildNode":
                $nodeTypeSpecification = $this->removeTetheredNodeFromNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "remove SuperType":
                $nodeTypeSpecification = $this->removeSuperTypeFromNodeTypeSpecification($nodeTypeSpecification);
                return $this->refineSpecification($nodeTypeSpecification);
            case "exit":
                throw new StopCommandException();
            default:
                throw new \InvalidArgumentException(sprintf("Unkonwn option %s", $choice));
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
        $name = $this->output->ask("Icon: ");
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

    protected function addMixinToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->select("Mixin: ", $this->nodeTypeNameSpecificationFactory->getExistingMixinNodeTypeNames());
        if (is_string($name)) {
            $nodeTypeName = NodeTypeNameSpecification::fromString($name);
            return $nodeTypeSpecification->withSuperType($nodeTypeName);
        } else {
            return $nodeTypeSpecification;
        }
    }

    protected function addConstraintToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->select("Constraint: ", $this->nodeTypeNameSpecificationFactory->getExistingConstraintNodeTypeNames());
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

        return $nodeTypeSpecification->withTetheredNode($tetheredNode);
    }

    protected function addPropertyToNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $name = $this->output->ask("Property name: ");
        $type = $this->output->select("Property type: ", $this->propertySpecificationFactory->getTypeConfiguration());
        $allowedValues = null;

        if (
            in_array($type, $this->propertyTypesWithAllowedValues) &&
            $this->output->askConfirmation("Do you want to restrict the allowed values? (Y/n)", false)
        ) {
            /**
             * @var string $allowedValueList
             */
            $allowedValueList = $this->output->ask("Allowed values (comma separated): ");
            $allowedValues = Arrays::trimExplode(',', $allowedValueList);
            if ($type === 'integer') {
                $this->output->outputLine();
                foreach ($allowedValues as $key => $value) {
                    if (!is_numeric($value)) {
                        $this->output->outputLine(sprintf('Warning: Allowed value "%s" is not an integer value and will be ignored', $value));
                        unset($allowedValues[$key]);
                    }
                }
                ksort($allowedValues);
            }
        }

        if (!is_string($name) || !is_string($type)) {
            return $nodeTypeSpecification;
        }

        $propertySpecification = $this->propertySpecificationFactory->generatePropertySpecificationFromCliInput(
            trim($name),
            $type,
            $allowedValues
        );

        return $nodeTypeSpecification->withProperty($propertySpecification);
    }

    protected function removePropertyFromNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $propertiesByName = array_reduce(
            iterator_to_array($nodeTypeSpecification->nodeProperties),
            function (array $carry, PropertySpecification $property) {
                $carry[(string)$property->name] = $property;
                return $carry;
            },
            []
        );

        $name = $this->output->select("Property to remove: ", [...array_keys($propertiesByName), 'exit']);

        if (is_string($name) && array_key_exists($name, $propertiesByName)) {
            $nodeTypeSpecification = $nodeTypeSpecification->withoutProperty($propertiesByName[$name]);
            return $nodeTypeSpecification;
        }

        return $nodeTypeSpecification;
    }

    protected function removeTetheredNodeFromNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $tetheredNodesByName = array_reduce(
            iterator_to_array($nodeTypeSpecification->tetheredNodes),
            function (array $carry, TetheredNodeSpecification $tetheredNode) {
                $carry[(string)$tetheredNode->name] = $tetheredNode;
                return $carry;
            },
            []
        );

        $name = $this->output->select("ChildNode to remove: ", [...array_keys($tetheredNodesByName), 'exit']);

        if (is_string($name) && array_key_exists($name, $tetheredNodesByName)) {
            $nodeTypeSpecification = $nodeTypeSpecification->withoutTetheredNode($tetheredNodesByName[$name]);
            return $nodeTypeSpecification;
        }

        return $nodeTypeSpecification;
    }

    protected function removeSuperTypeFromNodeTypeSpecification(NodeTypeSpecification $nodeTypeSpecification): NodeTypeSpecification
    {
        $superTypeByName = array_reduce(
            iterator_to_array($nodeTypeSpecification->superTypes),
            function (array $carry, NodeTypeNameSpecification $nodeTypeName) {
                $carry[(string)$nodeTypeName] = $nodeTypeName;
                return $carry;
            },
            []
        );

        $name = $this->output->select("SuperType to remove: ", [...array_keys($superTypeByName), 'exit']);

        if (is_string($name) && array_key_exists($name, $superTypeByName)) {
            $nodeTypeSpecification = $nodeTypeSpecification->withoutSuperType($superTypeByName[$name]);
            return $nodeTypeSpecification;
        }

        return $nodeTypeSpecification;
    }
}
