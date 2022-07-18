<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Nodemerobis\Domain\Generator;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodePresetNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyPresetNameSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertySpecification;
use Sitegeist\Nodemerobis\Domain\Specification\PropertyTypeSpecification;
use Sitegeist\Nodemerobis\Domain\Specification\TetheredNodeSpecification;

class NodeTypeGenerator implements NodeTypeGeneratorInterface
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    public function generateNodeType(NodeTypeSpecification $nodeTypeSpecification): NodeType
    {
        $localConfiguration = [
            'ui' => [],
            'childNodes' => [],
            'properties' => []
        ];

        $localConfiguration['ui']['label'] = $nodeTypeSpecification->label?->label ?? $nodeTypeSpecification->name->getNickname();

        $superTypes = [];
        foreach ($nodeTypeSpecification->superTypes as $superTypeSpecification) {
            /** @var NodeType|null $superType  */
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
            /** @var PropertySpecification $nodeProperty  */
            $propertyConfiguration = [];
            $typeOrPreset = $nodeProperty->typeOrPreset;
            if ($typeOrPreset instanceof PropertyTypeSpecification) {
                $propertyConfiguration['type'] = $typeOrPreset->type;
            } elseif ($typeOrPreset instanceof PropertyPresetNameSpecification) {
                $propertyConfiguration['options']['preset'] = $typeOrPreset->presetName;
            }

            $propertyConfiguration['ui']['label'] = $nodeProperty->label?->label ?? $nodeProperty->name->name;

            if ($nodeProperty->group) {
                $propertyConfiguration['ui']['inspector']['group'] = $nodeProperty->group->groupName;
            }

            if ($nodeProperty->description) {
                $propertyConfiguration['ui']['help']['messsage'] = $nodeProperty->description->description;
            }

            if ($nodeProperty->isRequired) {
                $propertyConfiguration['validation']['Neos.Neos/Validation/NotEmptyValidator'] = [];
            }

            $localConfiguration['properties'][$nodeProperty->name->name] = $propertyConfiguration;
        }

        foreach ($nodeTypeSpecification->tetheredNodes as $tetheredNode) {

            /** @var TetheredNodeSpecification $tetheredNode */
            $typeOrPreset = $tetheredNode->typeOrPreset;
            if ($typeOrPreset instanceof NodeTypeNameSpecification) {
                $localConfiguration['childNodes'][$tetheredNode->name->name] = ['type' => $typeOrPreset->getFullName()];
            } elseif ($typeOrPreset instanceof TetheredNodePresetNameSpecification) {
                $localConfiguration['childNodes'][$tetheredNode->name->name] = ['preset' => $typeOrPreset->presetName];
            }
        }

        $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);

        # add missing groups
        foreach ($nodeTypeSpecification->nodeProperties as $nodeProperty) {
            /** @var PropertySpecification $nodeProperty */
            $groupName = $nodeProperty->group?->groupName ?? null;
            if ($groupName) {
                if ($nodeType->hasConfiguration('ui.groups.' . $groupName) == false) {
                    $localConfiguration['ui']['inspector']['groups'][$groupName] = [
                        'tab' => 'default',
                        'icon' => 'file-alt',
                        'label' => $groupName
                    ];
                    $nodeType = new NodeType($nodeTypeSpecification->name->getFullName(), $superTypes, $localConfiguration);
                }
            }
        }

        # add icon based on super type
        if ($nodeType->isOfType('Neos.Neos:Shortcut')) {
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
