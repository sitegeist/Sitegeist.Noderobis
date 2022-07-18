<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Domain\Specification;

use Neos\Flow\Annotations as Flow;

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

    public function generatePropertySpecificationFromCliInput(string $name, string $type): PropertySpecification
    {
        $typeOrPreset = null;
        if (is_array($this->typeConfiguration) && array_key_exists($type, $this->typeConfiguration)) {
            $typeOrPreset = new PropertyTypeSpecification($type);
        } elseif (str_starts_with($type, "preset:") && is_array($this->presetConfiguration)) {
            $preset = substr($type, 7);
            if (array_key_exists('preset:' . $preset, $this->presetConfiguration)) {
                $typeOrPreset = new PropertyPresetNameSpecification($preset);
            }
        }

        if (is_null($typeOrPreset)) {
            throw new \InvalidArgumentException($type . ' is no valid type pr preset');
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
            foreach ($this->presetConfiguration as $name => $configuration) {
                $options[] = 'preset:' . $name;
            }
        }

        return $options;
    }
}
