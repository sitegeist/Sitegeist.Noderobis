<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Specification\BaseType;
use Sitegeist\Noderobis\Domain\Specification\IconNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationFactory;
use Sitegeist\Noderobis\Domain\Wizard\DeterminePrimarySuperTypeWizard;
use Sitegeist\Noderobis\Domain\Wizard\GenerateCodeWizard;
use Sitegeist\Noderobis\Domain\Wizard\GenerateNodeTypeWizard;
use Sitegeist\Noderobis\Domain\Wizard\SpecificationRefinementWizard;
use Sitegeist\Noderobis\Domain\Wizard\DetermineFlowPackageWizard;

#[Flow\Scope("singleton")]
class KickstartCommandController extends CommandController
{
    #[Flow\Inject]
    protected NodeTypeGenerator $nodeTypeGenerator;

    #[Flow\Inject]
    protected PropertySpecificationFactory $propertySpecificationFactory;

    #[Flow\Inject]
    protected TetheredNodeSpecificationFactory $tetheredNodeSpecificationFactory;

    #[Flow\Inject]
    protected NodeTypeNameSpecificationFactory $nodeTypeNameSpecificationFactory;

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $mixin
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $mixin (optional) Mixin-types to add as SuperTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names and property-NodeType seperated by a colon, can be used multiple times
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function documentCommand(string $name, ?string $packageKey = null, array $mixin = [], array $childNode = [], array $property = [], ?bool $abstract = null, bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Document, $name, $packageKey, $mixin, $childNode, $property, $abstract, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $mixin
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $mixin (optional) Mixin-types to add as SuperTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names and property-NodeType seperated by a colon, can be used multiple times
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function contentCommand(string $name, ?string $packageKey = null, array $mixin = [], array $childNode = [], array $property = [], ?bool $abstract = null, bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Content, $name, $packageKey, $mixin, $childNode, $property, $abstract, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $mixin
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $mixin (optional) Mixin-types to add as SuperTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names and property-NodeType seperated by a colon, can be used multiple times
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function mixinCommand(string $name, ?string $packageKey = null, array $mixin = [], array $childNode = [], array $property = [], bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Mixin, $name, $packageKey, $mixin, $childNode, $property, true, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $mixin
     *
     * @param BaseType $baseType Base type (Content, Document or Mixin)
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $mixin (optional) Mixin-types to add as SuperTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names and property-NodeType seperated by a colon, can be used multiple times
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function nodetypeCommand(BaseType $baseType, string $name, ?string $packageKey = null, array $mixin = [], array $childNode = [], array $property = [], ?bool $abstract = null, bool $yes = false): void
    {
        $determineFlowPackageWizard = new DetermineFlowPackageWizard();
        $package = $determineFlowPackageWizard->determineFlowPackage($packageKey);

        $determineSuperTypeWizard = new DeterminePrimarySuperTypeWizard();
        $primarySuperType = $determineSuperTypeWizard->determinePrimarySuperType($baseType, $package);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':' . $baseType->value . '.' . $name),
            $primarySuperType ? NodeTypeNameSpecificationCollection::fromStringArray([$primarySuperType->getName(), ...$mixin]) : new NodeTypeNameSpecificationCollection(),
            $this->propertySpecificationFactory->generatePropertySpecificationCollectionFromCliInputArray($property),
            $this->tetheredNodeSpecificationFactory->generateTetheredNodeSpecificationCollectionFromCliInputArray($childNode),
            is_bool($abstract) ? $abstract : !($primarySuperType && ($primarySuperType->isOfType('Neos.Neos:Document') || $primarySuperType->isOfType('Neos.Neos:Content')))
        );

        if (!$yes) {
            $specificationRefinementWizard = new SpecificationRefinementWizard($this->output);
            $nodeTypeSpecification = $specificationRefinementWizard->refineSpecification($nodeTypeSpecification);
        }

        $this->output->outputLine();
        $this->output->outputLine($nodeTypeSpecification->__toString());

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $generateCodeWizard = new GenerateCodeWizard($this->output);
        $generateCodeWizard->generateCode($nodeType, $package, $yes);
    }
}
