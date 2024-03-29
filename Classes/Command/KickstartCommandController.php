<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Specification\BaseType;
use Sitegeist\Noderobis\Domain\Specification\CliCommand;
use Sitegeist\Noderobis\Domain\Specification\IconNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeLabelSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\OptionsSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationFactory;
use Sitegeist\Noderobis\Domain\Wizard\DeterminePrimarySuperTypeWizard;
use Sitegeist\Noderobis\Domain\Wizard\GenerateCodeWizard;
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

    protected NodeTypeManager $nodeTypeManager;

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $crRegistry): void
    {
        $this->nodeTypeManager = $crRegistry->get(ContentRepositoryId::fromString('default'))->getNodeTypeManager();
    }

    public const OPTION_PATH = 'noderobis.__cliInternal';

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
        $this->nodetypeCommand(BaseType::Document, $name, $packageKey, $superType, $childNode, $property, $icon, $label, $abstract, $yes);
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
     * @phpstan-param array<int, string> $superType
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
            $label ? new NodeTypeLabelSpecification($label) : null,
            $icon ? new IconNameSpecification($icon) : null,
        );

        if (!$yes) {
            $specificationRefinementWizard = new SpecificationRefinementWizard($this->output);
            $nodeTypeSpecification = $specificationRefinementWizard->refineSpecification($nodeTypeSpecification);
        }

        $this->output->outputLine();
        $this->output->outputLine($nodeTypeSpecification->__toString());

        $cliCommand = CliCommand::create($baseType, $primarySuperType, $nodeTypeSpecification);
        $optionsSpecification = $nodeTypeSpecification->optionsSpecification ?? new OptionsSpecification([]);
        $optionsSpecification = $optionsSpecification->withOption(static::OPTION_PATH, json_encode($cliCommand->asArray()));

        $nodeTypeSpecification = $nodeTypeSpecification->withOptionsSpecification($optionsSpecification);

        $nodeType = $this->nodeTypeGenerator->generateNodeType($nodeTypeSpecification);

        $generateCodeWizard = new GenerateCodeWizard($this->output);
        $generateCodeWizard->generateCode($nodeType, $package, $yes);
    }

    /**
     * @param string $nodeType
     * @return void
     */
    public function refineCommand(string $nodeType): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
        if (!$nodeType instanceof NodeType) {
            $this->outputLine('NodeType ' . $nodeType . ' not found');
            $this->quit(1);
        }

        $cliSetting = $nodeType->getConfiguration('options.' . static::OPTION_PATH);

        if (!is_string($cliSetting)) {
            $this->outputLine(sprintf('configuration option "%s" was not found in NodeType "%s"', static::OPTION_PATH, $nodeType->name->value));
            $this->quit(1);
            return;
        }

        $cliCommandConfiguration = json_decode($cliSetting, true);

        if (!is_array($cliCommandConfiguration)) {
            $this->outputLine(sprintf('configuration option "%s" was in expected format in NodeType "%s"', static::OPTION_PATH, $nodeType->name->value));
            $this->quit(1);
        }

        $cliCommand = CliCommand::fromConfiguration($cliCommandConfiguration);

        $this->forward(
            $cliCommand->command,
            'Sitegeist\\Noderobis\\Command\\KickstartCommandController',
            $cliCommand->arguments
        );
    }
}
