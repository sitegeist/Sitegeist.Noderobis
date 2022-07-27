<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use Sitegeist\Noderobis\Utility\ConfigurationUtility;

class PropertySpecificationFactory
{
    /**
     * @var array<string, mixed>|null
     * @Flow\InjectConfiguration(package="Neos.Neos", path="nodeTypes.presets.properties")
     */
    protected $presetConfiguration;

    /**
     * @var array<string, mixed>|null
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.dataTypes")
     */
    protected $typeConfiguration;

    /**
     * @param array<int, string> $input
     * @return PropertySpecificationCollection
     */
    public function generatePropertySpecificationCollectionFromCliInputArray(array $input): PropertySpecificationCollection
    {
        $items = [];
        foreach ($input as $item) {
            list($name, $config) = explode(':', $item, 2);
            $items[] = $this->generatePropertySpecificationFromCliInput($name, $config);
        }
        return new PropertySpecificationCollection(... $items);
    }

    public function generatePropertySpecificationFromCliInput(string $name, string $type): PropertySpecification
    {
        $typeOrPreset = null;
        if (is_array($this->typeConfiguration) && array_key_exists($type, $this->typeConfiguration)) {
            $typeOrPreset = new PropertyTypeSpecification($type);
        } elseif (str_starts_with($type, "preset.") && is_array($this->presetConfiguration)) {
            $preset = substr($type, 7);
            $presetConfiguration = Arrays::getValueByPath($this->presetConfiguration, $preset);
            if (is_array($presetConfiguration) && array_key_exists('type', $presetConfiguration)) {
                $typeOrPreset = new PropertyPresetNameSpecification($preset);
            }
        }

        if (is_null($typeOrPreset)) {
            throw new \InvalidArgumentException($type . ' is no valid type or preset');
        }

        return new PropertySpecification(
            new PropertyNameSpecification($name),
            $typeOrPreset
        );
    }

    /**
     * @return array<int, string>
     */
    public function getTypeConfiguration(): array
    {
        $options = [];

        if (is_array($this->typeConfiguration)) {
            foreach ($this->typeConfiguration as $name => $configuration) {
                $options[] = $name;
            }
        }

        if (is_array($this->presetConfiguration)) {
            if (is_array($this->presetConfiguration)) {
                $presetPathes = ConfigurationUtility::findConfigurationPathesByKey($this->presetConfiguration, 'type');
                foreach ($presetPathes as $presetPathe) {
                    $options[] = 'preset.' . $presetPathe;
                }
            }
        }

        return $options;
    }
}
