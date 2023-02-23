<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use Sitegeist\Noderobis\Utility\ConfigurationUtility;

class PropertySpecificationFactory
{
    /** @var array<string, mixed>|null */
    #[Flow\InjectConfiguration("nodeTypes.presets.properties", "Neos.Neos")]
    protected array|null $presetConfiguration = null;

    /** @var array<string, mixed>|null */
    #[Flow\InjectConfiguration("userInterface.inspector.dataTypes", "Neos.Neos")]
    protected array|null $typeConfiguration = null;

    /**
     * @param array<int, string> $input
     */
    public function generatePropertySpecificationCollectionFromCliInputArray(array $input): PropertySpecificationCollection
    {
        $items = [];
        foreach ($input as $item) {
            if (!is_string($item)) {
                continue;
            }
            $propertyConfig = explode(':', $item, 3);
            $name = $propertyConfig[0] ?? null;
            $typeOrPreset = $propertyConfig[1] ?? null;
            if (!$name) {
                continue;
            }
            if (!$typeOrPreset) {
                continue;
            }

            $allowedValues = $propertyConfig[2] ?? null;
            $items[] = $this->generatePropertySpecificationFromCliInput($name, $typeOrPreset, $allowedValues ? explode(',', $allowedValues) : null);
        }
        return new PropertySpecificationCollection(... $items);
    }

    /**
     * @param string $name
     * @param string $type
     * @param array<int|string>|null $allowedValues
     * @return PropertySpecification
     */
    public function generatePropertySpecificationFromCliInput(string $name, string $type, ?array $allowedValues = null): PropertySpecification
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

        $allowedValuesSpecification = null;
        if ($allowedValues && $typeOrPreset instanceof PropertyTypeSpecification) {
            if ($typeOrPreset->type === 'integer') {
                $allowedValues = array_map(
                    function (mixed $value) use ($name) {
                        if (is_numeric($value)) {
                            return (int)$value;
                        }
                        throw new \InvalidArgumentException('At least one allowed value for ' . $name . ' is not a valid integer');
                    },
                    $allowedValues
                );
            } else {
                $allowedValues = array_map(
                    function (mixed $value) {
                        return (string)$value;
                    },
                    $allowedValues
                );
            }
            $allowedValuesSpecification = new PropertyAllowedValuesSpecification(...$allowedValues);
        }

        return new PropertySpecification(
            new PropertyNameSpecification($name),
            $typeOrPreset,
            $allowedValuesSpecification
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
            $presetPathes = ConfigurationUtility::findConfigurationPathesByKey($this->presetConfiguration, 'type');
            foreach ($presetPathes as $presetPathe) {
                $options[] = 'preset.' . $presetPathe;
            }
        }

        return $options;
    }
}
