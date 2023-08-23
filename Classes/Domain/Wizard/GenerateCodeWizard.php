<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Wizard;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Neos\Domain\Service\DefaultPrototypeGeneratorInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Sitegeist\Noderobis\Domain\Generator\CreateFusionRendererModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\IncludeFusionFromNodeTypesModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\ModificationGeneratorInterface;
use Sitegeist\Noderobis\Domain\Modification\ModificationCollection;

class GenerateCodeWizard
{
    #[Flow\Inject]
    protected CreateNodeTypeYamlFileModificationGenerator $createNodeTypeYamlFileModificationGenerator;

    #[Flow\Inject]
    protected CreateFusionRendererModificationGenerator $createFusionRendererModificationGenerator;

    #[Flow\Inject]
    protected IncludeFusionFromNodeTypesModificationGenerator $includeFusionFromNodeTypesModificationGenerator;

    /** @var array<string|string>|null */
    #[Flow\InjectConfiguration("modificationGenerators")]
    protected array|null $modificationGeneratorConfig;

    #[Flow\Inject]
    protected ObjectManagerInterface $objectManager;

    public function __construct(
        private readonly ConsoleOutput $output
    ) {
    }

    public function generateCode(NodeType $nodeType, FlowPackageInterface $package, bool $yes = false): void
    {
        $modificationGenerators = [];

        if ($this->modificationGeneratorConfig) {
            foreach ($this->modificationGeneratorConfig as $key => $generatorClassName) {
                if (empty($generatorClassName)) {
                    continue;
                }
                if (!class_exists($generatorClassName)) {
                    throw new InvalidTypeException('Generator Class ' . $generatorClassName . ' does not exist');
                }
                $generator = $this->objectManager->get($generatorClassName);
                if (!$generator instanceof ModificationGeneratorInterface) {
                    throw new InvalidTypeException('Generator Class ' . $generatorClassName . ' does not implement interface ' . ModificationGeneratorInterface::class);
                }
                $modificationGenerators[$key] = $generator->generateModification($package, $nodeType);
            }
        }

        $collection = new ModificationCollection(
            ...$modificationGenerators
        );

        if (!$yes && $collection->isConfirmationRequired()) {
            $this->output->outputLine();
            $this->output->outputLine("<info>The given specifications will result in the following modifications</info>");
            $this->output->outputLine($collection->getDescription());

            $this->output->outputLine();
            $this->output->outputLine("Confirmation is required.");
            if ($this->output->askConfirmation("Shall we proceed (Y/n)?") == false) {
                throw new StopCommandException();
            }
        }

        $this->output->outputLine();
        $this->output->outputLine("<info>The following modifications are applied</info>");
        $this->output->outputLine($collection->getDescription());

        $collection->apply();
    }
}
