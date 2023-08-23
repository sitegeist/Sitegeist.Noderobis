<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Wizard;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Package\FlowPackageInterface;
use Sitegeist\Noderobis\Domain\Specification\BaseType;

class DeterminePrimarySuperTypeWizard
{
    #[Flow\Inject]
    protected NodeTypeManager $nodeTypeManager;

    /** @var array<string|string>|string|null */
    #[Flow\InjectConfiguration("superTypeDefaults")]
    protected array|string|null $superTypeDefaults;

    public function determinePrimarySuperType(BaseType $baseType, FlowPackageInterface $flowPackage): ?NodeType
    {
        $primarySuperTypeInPackageNamespace = $flowPackage->getPackageKey() . ':' . $baseType->value;
        if ($this->nodeTypeManager->hasNodeType($primarySuperTypeInPackageNamespace)) {
            return $this->nodeTypeManager->getNodeType($primarySuperTypeInPackageNamespace);
        }

        if (is_array($this->superTypeDefaults) && array_key_exists($baseType->value, $this->superTypeDefaults)) {
            if ($this->nodeTypeManager->hasNodeType($this->superTypeDefaults[$baseType->value])) {
                return $this->nodeTypeManager->getNodeType($this->superTypeDefaults[$baseType->value]);
            }
        }

        return null;
    }
}
