<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Sitegeist\Noderobis\Domain\Specification\CliCommand;

trait CliCommandInfoTrait
{
    public function createCliCommandInfoForNodeType(NodeType $nodeType): string
    {
        $cliConfig = $nodeType->getConfiguration('options.noderobis.cli');
        if ($cliConfig && is_array($cliConfig) && array_key_exists('command', $cliConfig)) {
            $command = new CliCommand(
                $cliConfig['command'],
                $cliConfig['arguments'] ?? []
            );
            return 'created via: ' . $command->asString();
        }
        return '';
    }
}
