<?php

declare(strict_types=1);

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

namespace Sitegeist\Nodemerobis\Domain\Modification;

use Neos\Utility\Files;

class CreateFileModification implements ModificationInterface
{
    protected ?bool $fileExists = null;

    public function __construct(
        public readonly string $filePath,
        public readonly string $content
    ) {
    }

    public function isConfirmationRequired(): bool
    {
        return $this->doesFileExist();
    }

    public function getDescription(): string
    {
        $path = $this->filePath;
        if (substr($this->filePath, 0, strlen(FLOW_PATH_ROOT)) == FLOW_PATH_ROOT) {
            $path = substr($path, strlen(FLOW_PATH_ROOT));
        }

        if ($this->doesFileExist()) {
            return sprintf("Overwrite file %s", $path);
        } else {
            return sprintf("Create file %s", $path);
        }
    }

    public function apply(): void
    {
        $dirname = dirname($this->filePath);

        if (!file_exists($dirname)) {
            Files::createDirectoryRecursively($dirname);
        }

        file_put_contents($this->filePath, $this->content);
    }

    protected function doesFileExist(): bool
    {
        if (is_null($this->fileExists)) {
            $this->fileExists = file_exists($this->filePath);
        }
        return $this->fileExists;
    }
}
