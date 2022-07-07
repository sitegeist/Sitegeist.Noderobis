<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Package\SitegeistageInterface;

class NodeTypeSpecificationFactory
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @param SitegeistageInterface $package
     * @param string $name
     * @param string[] $superTypes
     * @param string[] $childnodeCliArguments
     * @param string[] $propertCliArguments
     * @param bool $abstract
     * @return NodeTypeSpecification
     */
    public function createForPackageAndCliArguments(SitegeistageInterface $package, string $name, array $superTypes, array $childnodeCliArguments, array $propertCliArguments, bool $abstract = false): NodeTypeSpecification
    {

        // prefix nodeTypes with package key
        if (strpos($name, ':') === false) {
            $name = $package->getPackageKey() . ':' . $name;
        }

        // prefix superTypes with package key
        $superTypes = array_map(
            function (string $name) use ($package) {
                if (strpos($name, ':') === false) {
                    return $package->getPackageKey() . ':' . $name;
                } else {
                    return $name;
                }
            },
            $superTypes
        );

        return NodeTypeSpecification::fromCliArguments($name, $superTypes, $childnodeCliArguments, $propertCliArguments, $abstract);
    }
}
