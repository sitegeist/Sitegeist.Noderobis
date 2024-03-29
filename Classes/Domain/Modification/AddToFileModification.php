<?php

declare(strict_types=1);

namespace Sitegeist\Noderobis\Domain\Modification;

use Neos\Utility\Files;

class AddToFileModification implements ModificationInterface
{
    protected ?bool $fileExists = null;
    protected ?bool $contentAlreadyExists = null;

    public function __construct(
        public readonly string $filePath,
        public readonly string $content,
        public readonly bool $prepend = false
    ) {
    }

    public function isConfirmationRequired(): bool
    {
        if (!$this->doesFileExist()) {
            return false;
        } elseif ($this->doesContentAlreadyExist()) {
            return false;
        }
        return true;
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

        if ($this->doesFileExist() === false) {
            file_put_contents($this->filePath, $this->content);
        } elseif ($this->doesContentAlreadyExist() === false) {
            $previousContent = file_get_contents($this->filePath);
            if ($previousContent === false) {
                throw new \Exception(sprintf('File %s could not be read', $this->filePath));
            }
            if ($this->doesContentAlreadyExist() === false) {
                $newContent = $this->prepend ?  $this->content .  PHP_EOL . $previousContent : $previousContent . PHP_EOL . $this->content;
                file_put_contents($this->filePath, $newContent);
            }
        }
    }

    protected function doesFileExist(): bool
    {
        if (is_null($this->fileExists)) {
            $this->fileExists = file_exists($this->filePath);
        }
        return $this->fileExists;
    }

    protected function doesContentAlreadyExist(): bool
    {
        if (is_null($this->contentAlreadyExists)) {
            if (!$this->doesFileExist()) {
                $this->contentAlreadyExists = false;
            } else {
                $existingContent = file_get_contents($this->filePath);
                if ($existingContent === false) {
                    $this->contentAlreadyExists = false;
                } else {
                    $this->contentAlreadyExists = strpos($existingContent, $this->content) !== false;
                }
            }
        }
        return $this->contentAlreadyExists;
    }
}
