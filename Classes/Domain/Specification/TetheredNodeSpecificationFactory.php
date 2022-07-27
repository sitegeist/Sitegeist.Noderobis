<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use http\Exception\InvalidArgumentException;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use Sitegeist\Noderobis\Utility\ConfigurationUtility;

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

    /**
     * @param array<int, string> $input
     * @return TetheredNodeSpecificationCollection
     */
    public function generateTetheredNodeSpecificationCollectionFromCliInputArray(array $input): TetheredNodeSpecificationCollection
    {
        $items = [];
        foreach ($input as $item) {
            list($name, $config) = explode(':', $item, 2);
            $items[] = $this->generateTetheredNodeSpecificationFromCliInput($name, $config);
        }
        return new TetheredNodeSpecificationCollection(... $items);
    }

    public function generateTetheredNodeSpecificationFromCliInput(string $name, string $type): TetheredNodeSpecification
    {
        $typeOrPreset = null;
        if ($this->nodeTypeManager->hasNodeType($type)) {
            $typeOrPreset = NodeTypeNameSpecification::fromString($type);
        } elseif (str_starts_with($type, "preset.") && is_array($this->presetConfiguration)) {
            $preset = substr($type, 7);
            $presetConfiguration = Arrays::getValueByPath($this->presetConfiguration, $preset);
            if (is_array($presetConfiguration) && array_key_exists('type', $presetConfiguration)) {
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
            $presetPathes = ConfigurationUtility::findConfigurationPathesByKey($this->presetConfiguration, 'type');
            foreach ($presetPathes as $presetPathe) {
                $options[] = 'preset.' . $presetPathe;
            }
        }

        return $options;
    }
}
