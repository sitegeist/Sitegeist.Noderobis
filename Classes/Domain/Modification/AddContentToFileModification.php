<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Modification;

use Neos\Utility\Files;

class AddContentToFileModification implements ModificationInterface
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $content,
        public readonly bool $prepend = false
    ) {
    }

    public function isConfirmationRequired(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        $path = $this->filePath;
        if (substr($this->filePath, 0, strlen(FLOW_PATH_ROOT)) == FLOW_PATH_ROOT) {
            $path = substr($path, strlen(FLOW_PATH_ROOT));
        }
        return sprintf("Ensure file %s contains '%s'", $path, $this->content);
    }

    public function apply(): void
    {
        $dirname = dirname($this->filePath);

        if (!file_exists($dirname)) {
            Files::createDirectoryRecursively($dirname);
        }

        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, $this->content);
        } else {
            $previousContent = file_get_contents($this->filePath);
            if ($previousContent === false) {
                throw new \Exception(sprintf('File %s could not be read', $this->filePath));
            }
            if (strpos($previousContent, $this->content) === false) {
                $newContent = $this->prepend ?  $this->content .  PHP_EOL . $previousContent : $previousContent . PHP_EOL . $this->content;
                file_put_contents($this->filePath, $newContent);
            }
        }
    }
}
