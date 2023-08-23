<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Specification;

use Neos\ContentRepository\Core\NodeType\NodeType;

class CliCommand
{
    /**
     * @param string $command
     * @param array<mixed> $arguments
     */
    public function __construct(
        public readonly string $command,
        public readonly array $arguments = []
    ) {
    }

    public static function create(BaseType $baseType, ?NodeType $primarySuperType, NodeTypeSpecification $nodeTypeSpecification): static
    {
        $name = strtolower($baseType->value);
        $arguments = [];

        $nameArgument = $nodeTypeSpecification->name->localName;
        if (str_starts_with($nameArgument, $baseType->value . '.')) {
            $nameArgument = preg_replace('/^' . preg_quote($baseType->value . '.', '/') . '/', '', $nameArgument);
        }
        $arguments['name'] = $nameArgument;
        $arguments['packageKey'] = $nodeTypeSpecification->name->packageKey;

        /**
         * @var NodeTypeNameSpecification $superType
         */
        foreach ($nodeTypeSpecification->superTypes->getIterator() as $superType) {
            if ($primarySuperType && $primarySuperType->getName() === $superType->getFullName()) {
                continue;
            }
            $arguments['superType'][] = (string)$superType;
        }

        /**
         * @var PropertySpecification $propertySpecification
         */
        foreach ($nodeTypeSpecification->nodeProperties->getIterator() as $propertySpecification) {
            $arguments['property'][] = (string)$propertySpecification;
        }

        /**
         * @var TetheredNodeSpecification $tetheredNode
         */
        foreach ($nodeTypeSpecification->tetheredNodes->getIterator() as $tetheredNode) {
            $arguments['childNode'][] = (string)$tetheredNode;
        }

        if ($nodeTypeSpecification->abstract) {
            $arguments['abstract'] = true;
        }

        if ($nodeTypeSpecification->icon) {
            $arguments['icon'] = $nodeTypeSpecification->icon->name;
        }

        if ($nodeTypeSpecification->label) {
            $arguments['label'] = $nodeTypeSpecification->label->label;
        }

        return new static(
            $name,
            $arguments
        );
    }

    public function asString(): string
    {
        $command = './flow kickstart:' . $this->command;
        foreach ($this->arguments as $argumentName => $value) {
            if (is_string($value)) {
                $command .= ' --' . $argumentName . ' ' . $value;
            } elseif (is_bool($value) && $value === true) {
                $command .= ' --' . $argumentName;
            } if (is_array($value)) {
                foreach ($value as $item) {
                    $command .= ' --' . $argumentName . ' ' . $item;
                }
            }
        }
        return $command;
    }

    /**
     * @return array{'command':string, 'arguments':mixed[]}
     */
    public function asArray(): array
    {
        return [
            'command' => $this->command,
            'arguments' => $this->arguments
        ];
    }

    /**
     * @param mixed $config
     */
    public static function fromConfiguration(mixed $config): static
    {
        if (is_array($config) && array_key_exists('command', $config)) {
            return new static(
                $config['command'],
                $config['arguments'] ?? []
            );
        } else {
            throw new \InvalidArgumentException('Not a valid cli command config');
        }
    }
}
