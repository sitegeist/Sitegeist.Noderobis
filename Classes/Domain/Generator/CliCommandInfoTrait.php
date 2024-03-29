<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Generator;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Sitegeist\Noderobis\Command\KickstartCommandController;
use Sitegeist\Noderobis\Domain\Specification\CliCommand;

trait CliCommandInfoTrait
{
    public function createCliCommandInfoForNodeType(NodeType $nodeType): string
    {
        $cli = $nodeType->getConfiguration('options.' . KickstartCommandController::OPTION_PATH);
        if (is_string($cli)) {
            $cliConfig = json_decode($cli, true);
            if ($cliConfig && is_array($cliConfig) && array_key_exists('command', $cliConfig)) {
                $command = new CliCommand(
                    $cliConfig['command'],
                    $cliConfig['arguments'] ?? []
                );
                return 'created from Sitegeist/Noderobis via: ' . $command->asString();
            }
        }
        return '';
    }
}
