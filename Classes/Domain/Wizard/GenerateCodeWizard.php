<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Wizard;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Noderobis\Domain\Generator\CreateFusionRendererModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Modification\ModificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;

class GenerateCodeWizard
{
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

    public function __construct(
        private readonly ConsoleOutput $output
    ) {
    }

    public function generateCode(NodeType $nodeType, FlowPackageInterface $package, bool $yes = false): void
    {
        $collection = new ModificationCollection(
            $this->createNodeTypeYamlFileModificationGenerator->generateModification($package, $nodeType),
            $this->createFusionRendererModificationGenerator->generateModification($package, $nodeType)
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
