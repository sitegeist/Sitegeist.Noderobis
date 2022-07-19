<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use http\Exception\InvalidArgumentException;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

class TetheredNodeSpecificationFactory
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @var array<string, mixed>|null
     * @Flow\InjectConfiguration(package="Neos.Neos", path="nodeTypes.presets.childNodes")
     */
    protected $presetConfiguration;

    public function generateTetheredNodeSpecificationFromCliInput(string $name, string $type): TetheredNodeSpecification
    {
        $typeOrPreset = null;
        if ($this->nodeTypeManager->hasNodeType($type)) {
            $typeOrPreset = NodeTypeNameSpecification::fromString($type);
        } elseif (str_starts_with($type, "preset:") && is_array($this->presetConfiguration)) {
            $preset = substr($type, 7);
            if (array_key_exists($preset, $this->presetConfiguration)) {
                $typeOrPreset = new TetheredNodePresetNameSpecification($preset);
            }
        }

        if (is_null($typeOrPreset)) {
            throw new InvalidArgumentException($type . ' is no valid type or preset');
        }

        return new TetheredNodeSpecification(
            new TetheredNodeNameSpecification($name),
            $typeOrPreset
        );
    }

    /**
     * @return array<int, string>
     */
    public function getTypeConfiguration(): array
    {
        $options = [];

        foreach ($this->nodeTypeManager->getNodeTypes(false) as $nodeType) {
            $options[] = $nodeType->getName();
        }

        if (is_array($this->presetConfiguration)) {
            foreach ($this->presetConfiguration as $name => $configuration) {
                $options[] = 'preset:' . $name;
            }
        }

        return $options;
    }
}
