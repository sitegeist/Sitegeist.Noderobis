<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\Utility\Exception\InvalidTypeException;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodePresetNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyPresetNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertyTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecification;

class NodeTypeGenerator implements NodeTypeGeneratorInterface
{
    protected NodeTypeManager $nodeTypeManager;

    public function injectNodeTypeManager(NodeTypeManager $nodeTypeManager): void
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function generateNodeType(NodeTypeSpecification $nodeTypeSpecification): NodeType
    {
        $localConfiguration = [
            'ui' => [
                'label' => $nodeTypeSpecification->label?->label ?? $nodeTypeSpecification->name->getNickname()
            ]
        ];

        $superTypes = [];
        foreach ($nodeTypeSpecification->superTypes as $superTypeSpecification) {
            /** @var NodeType|null $superType */
            $superType = $this->nodeTypeManager->getNodeType($superTypeSpecification->getFullName());
            if ($superType instanceof NodeType) {
                $superTypes[] = $superType;
            } else {
                throw new \InvalidArgumentException(sprintf('Unknown supertype %s', $superTypeSpecification->getFullName()));
            }
        }

        if ($nodeTypeSpecification->abstract) {
            $localConfiguration['abstract'] = true;
        }

        foreach ($nodeTypeSpecification->nodeProperties as $nodeProperty) {
            /** @var PropertySpecification $nodeProperty */
            $propertyConfiguration = [];
            $typeOrPreset = $nodeProperty->typeOrPreset;
            $allowedValues = $nodeProperty->allowedValues;
            if ($typeOrPreset instanceof PropertyTypeSpecification) {
                $propertyConfiguration['type'] = $typeOrPreset->type;

                if ($allowedValues) {
                    if ($propertyConfiguration['type'] === 'array') {
                        $propertyConfiguration['ui']['inspector']['editorOptions']['multiple'] = true;
                    }

                    $allowedValuesConfig = [];
                    foreach ($allowedValues->allowedValues as $value) {
                        if ($propertyConfiguration['type'] === 'integer' && !is_numeric($value)) {
                            continue;
                        }
                        $allowedValuesConfig[$value] = ['label' => $value];
                    }
                    $propertyConfiguration['ui']['inspector']['editor'] = 'Neos.Neos/Inspector/Editors/SelectBoxEditor';
                    $propertyConfiguration['ui']['inspector']['editorOptions']['values'] = $allowedValuesConfig;
                }
            } elseif ($typeOrPreset instanceof PropertyPresetNameSpecification) {
                $propertyConfiguration['options']['preset'] = $typeOrPreset->presetName;
            }

            $propertyConfiguration['ui']['inspector']['group'] = 'default';
            $propertyConfiguration['ui']['label'] = $nodeProperty->label?->label ?? $nodeProperty->name->name;

            if ($nodeProperty->description) {
                $propertyConfiguration['ui']['help']['messsage'] = $nodeProperty->description->description;
            }

            if ($nodeProperty->isRequired) {
                $propertyConfiguration['validation']['Neos.Neos/Validation/NotEmptyValidator'] = [];
            }

            $localConfiguration['properties'][$nodeProperty->name->name] = $propertyConfiguration;
        }

        foreach ($nodeTypeSpecification->tetheredNodes as $tetheredNode) {
            if (array_key_exists('childNodes', $localConfiguration) === false) {
                $localConfiguration['childNodes'] = [];
            }
            /** @var TetheredNodeSpecification $tetheredNode */
            $typeOrPreset = $tetheredNode->typeOrPreset;
            if ($typeOrPreset instanceof NodeTypeNameSpecification) {
                $localConfiguration['childNodes'][$tetheredNode->name->name] = ['type' => $typeOrPreset->getFullName()];
            } elseif ($typeOrPreset instanceof TetheredNodePresetNameSpecification) {
                $localConfiguration['childNodes'][$tetheredNode->name->name] = ['preset' => $typeOrPreset->presetName];
            }
        }

        # node type is created temporary to resolve presets and get access to inherited properties and groups
        $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);

        # assign groups and reload if changed to  inspector properties
        foreach ($nodeTypeSpecification->nodeProperties as $nodeProperty) {
            if (array_key_exists('properties', $localConfiguration) === false) {
                $localConfiguration['properties'] = [];
            }
            /** @var PropertySpecification $nodeProperty */
            $isInlineEditable = $nodeType->getConfiguration('properties.' . $nodeProperty->name->name . '.ui.inlineEditable');

            if (!$isInlineEditable) {
                $groupName = $nodeProperty->group?->groupName ?? 'default';
                $localConfiguration['properties'][$nodeProperty->name->name]['ui']['inspector']['group'] = $groupName;
                if ($nodeType->hasConfiguration('ui.groups.' . $groupName) == false) {
                    $localConfiguration['ui']['inspector']['groups'][$groupName] = [
                        'tab' => 'default',
                        'icon' => 'file-alt',
                        'label' => $groupName
                    ];
                    $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);
                }
                $localConfiguration['properties'][$nodeProperty->name->name]['ui']['reloadIfChanged'] = true;
            }
        }

        if ($nodeTypeSpecification->optionsSpecification?->options) {
            $localConfiguration['options'] = $nodeTypeSpecification->optionsSpecification->options;
        }

        # add icon based on specification or super type
        if ($nodeTypeSpecification->icon) {
            $localConfiguration['ui']['icon'] = $nodeTypeSpecification->icon->name;
        } elseif ($nodeType->isOfType('Neos.Neos:Shortcut')) {
            $localConfiguration['ui']['icon'] = 'share';
        } elseif ($nodeType->isOfType('Neos.Neos:Document')) {
            $localConfiguration['ui']['icon'] = 'file';
        } elseif ($nodeType->isOfType('Neos.Neos:ContentCollection')) {
            $localConfiguration['ui']['icon'] = 'folder-open';
        } elseif ($nodeType->isOfType('Neos.Neos:Content')) {
            $localConfiguration['ui']['icon'] = 'file-alt';
        } else {
            $localConfiguration['ui']['icon'] = 'question-circle';
        }

        $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);

        return $nodeType;
    }
}
