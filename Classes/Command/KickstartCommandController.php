<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Command;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Specification\BaseType;
use Sitegeist\Noderobis\Domain\Specification\IconNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\OptionsSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecification;
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
     * @phpstan-param array<int, string> $superType
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $superType (optional) superTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names, property-NodeType and allowed values (optional), seperated by a colon, can be used multiple times - examples `--property title:string` `--property format:string:portrait,landscape`
     * @param string $icon (optional) Node icon name
     * @param string $label (optional) Node label
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function documentCommand(string $name, ?string $packageKey = null, array $superType = [], array $childNode = [], array $property = [], ?string $icon = null, ?string $label = null, ?bool $abstract = null, bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Document, $name, $packageKey, $superType, $childNode, $property, $icon, $label,$abstract, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $superType
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $superType (optional) superTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names, property-NodeType and allowed values (optional), seperated by a colon, can be used multiple times - examples `--property title:string` `--property format:string:portrait,landscape`
     * @param string $icon (optional) Node icon name
     * @param string $label (optional) Node label
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function contentCommand(string $name, ?string $packageKey = null, array $superType = [], array $childNode = [], array $property = [], ?string $icon = null, ?string $label = null, ?bool $abstract = null, bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Content, $name, $packageKey, $superType, $childNode, $property, $icon, $label, $abstract, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $superType
     *
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $superType (optional) superTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names, property-NodeType and allowed values (optional), seperated by a colon, can be used multiple times - examples `--property title:string` `--property format:string:portrait,landscape`
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function mixinCommand(string $name, ?string $packageKey = null, array $superType = [], array $childNode = [], array $property = [], bool $yes = false): void
    {
        $this->nodetypeCommand(BaseType::Mixin, $name, $packageKey, $superType, $childNode, $property, null, null, true, $yes);
    }

    /**
     * @phpstan-param array<int, string> $property
     * @phpstan-param array<int, string> $childNode
     * @phpstan-param array<int, string> $mixin
     *
     * @param BaseType $baseType Base type (Content, Document or Mixin)
     * @param string $name Node Name, last part of NodeType
     * @param string $packageKey (optional) Package, uses fallback from configuration
     * @param array $superType (optional) superTypes, can be used multiple times
     * @param array $childNode (optional) childNode-names and childNode-NodeType seperated by a colon, can be used multiple times
     * @param array $property (optional) property-names, property-NodeType and allowed values (optional), seperated by a colon, can be used multiple times - examples `--property title:string` `--property format:string:portrait,landscape`
     * @param string|null $icon (optional) Icon name
     * @param string|null $label (optional) Node label
     * @param bool $abstract (optional) By default contents and documents are created non abstract
     * @param bool $yes (optional) Skip refinement-process and apply all modifications directly
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    protected function nodetypeCommand(BaseType $baseType, string $name, ?string $packageKey = null, array $superType = [], array $childNode = [], array $property = [], ?string $icon = null, ?string $label = null, ?bool $abstract = null, bool $yes = false): void
    {
        $determineFlowPackageWizard = new DetermineFlowPackageWizard();
        $package = $determineFlowPackageWizard->determineFlowPackage($packageKey);

        $determineSuperTypeWizard = new DeterminePrimarySuperTypeWizard();
        $primarySuperType = $determineSuperTypeWizard->determinePrimarySuperType($baseType, $package);

        $nodeTypeSpecification = new NodeTypeSpecification(
            NodeTypeNameSpecification::fromString($package->getPackageKey() . ':' . $baseType->value . '.' . ucfirst($name)),
            $primarySuperType ? NodeTypeNameSpecificationCollection::fromStringArray([$primarySuperType->getName(), ...$superType]) : new NodeTypeNameSpecificationCollection(),
            $this->propertySpecificationFactory->generatePropertySpecificationCollectionFromCliInputArray($property),
            $this->tetheredNodeSpecificationFactory->generateTetheredNodeSpecificationCollectionFromCliInputArray($childNode),
            is_bool($abstract) ? $abstract : !($primarySuperType && ($primarySuperType->isOfType('Neos.Neos:Document') || $primarySuperType->isOfType('Neos.Neos:Content'))),
            $label ? new NodeTypeLabelSpecification($label): null,
            $icon ? new IconNameSpecification($icon) : null,
        );

        if (!$yes) {
            $specificationRefinementWizard = new SpecificationRefinementWizard($this->output);
            $nodeTypeSpecification = $specificationRefinementWizard->refineSpecification($nodeTypeSpecification);
        }

        $this->output->outputLine();
        $this->output->outputLine($nodeTypeSpecification->__toString());

        $optionsSpecification = $nodeTypeSpecification->optionsSpecification ?? new OptionsSpecification([]);
        $optionsSpecification = $optionsSpecification->withOption('noderobis.cliCommand', $this->createCliCommandForLaterRestarting($baseType, $primarySuperType, $nodeTypeSpecification));
        $nodeTypeSpecification = $nodeTypeSpecification->withOptionsSpecification($optionsSpecification);

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $generateCodeWizard = new GenerateCodeWizard($this->output);
        $generateCodeWizard->generateCode($nodeType, $package, $yes);
    }

    protected function createCliCommandForLaterRestarting(BaseType $baseType, ?NodeType $primarySuperType, NodeTypeSpecification $nodeTypeSpecification): string
    {
        $command = './flow kickstart:' . strtolower($baseType->value);

        $nameArgument = $nodeTypeSpecification->name->localName;
        if (str_starts_with( $nameArgument, $baseType->value . '.')) {
            $nameArgument = preg_replace('/^' . preg_quote($baseType->value . '.', '/') . '/', '', $nameArgument);
        }
        $command .= ' --name ' . $nameArgument;

        $command .= ' --packageKey ' . $nodeTypeSpecification->name->packageKey;

        /**
         * @var NodeTypeNameSpecification $superType
         */
        foreach ($nodeTypeSpecification->superTypes->getIterator() as $superType) {
            if ($primarySuperType && $primarySuperType->getName() === $superType->getFullName()) {
                continue;
            }

            $command .= ' --superType ' . $superType;
        }

        /**
         * @var PropertySpecification $propertySpecification
         */
        foreach ($nodeTypeSpecification->nodeProperties->getIterator() as $propertySpecification) {
            $command .= ' --property ' . $propertySpecification;
        }

        /**
         * @var TetheredNodeSpecification $tetheredNode
         */
        foreach ($nodeTypeSpecification->tetheredNodes->getIterator() as $tetheredNode) {
            $command .= ' --childNode ' . $tetheredNode;
        }

        if ($nodeTypeSpecification->abstract) {
            $command .= ' --abstract';
        }

        if ($nodeTypeSpecification->icon) {
            $command .= ' --icon ' . $nodeTypeSpecification->icon->name;
        }

        if ($nodeTypeSpecification->label) {
            $command .= ' --label ' . $nodeTypeSpecification->label->label;
        }

        return $command;
    }
}
