<?php
declare(strict_types=1);

use Neos\Utility\ObjectAccess;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Sitegeist\Noderobis\Domain\Modification\WriteFileModification;

class WriteFileModificationTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('Test');
        mkdir('vfs://Test/Directory');
    }

    /**
     * @test
     */
    public function contentIsWrittenToSpecifiedLocation()
    {
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileDoesNotExist('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());

        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }

    /**
     * @test
     */
    public function existingFilesRequireConfirmation()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', 'OtherStuff');
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertTrue($modification->isConfirmationRequired());
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }
}
