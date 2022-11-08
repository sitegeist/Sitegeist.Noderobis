<?php
declare(strict_types=1);

use Neos\Utility\ObjectAccess;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Sitegeist\Noderobis\Domain\Modification\AddToFileModification;
use Sitegeist\Noderobis\Domain\Modification\WriteFileModification;

class AddToFileModificationTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('Test');
        mkdir('vfs://Test/Directory');
    }

    /**
     * @test
     */
    public function nonExistingFilesDoNotRequireConfirmation()
    {
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileDoesNotExist('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function existingFilesRequireConfirmationIfContentIsMissing()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', "OtherStuff");
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertTrue($modification->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function existingFilesDoNotRequireConfirmationIfContentExists()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', 'FileContent' . PHP_EOL . 'OtherStuff');
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function nonExistingFilesAreCreated()
    {
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileDoesNotExist('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }

    /**
     * @test
     */
    public function existingFilesRequireConfirmationsAndAppendContent()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', "OtherStuff");
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertTrue($modification->isConfirmationRequired());
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('OtherStuff' . PHP_EOL. 'FileContent' , file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }

    /**
     * @test
     */
    public function prependOptionEnsuresPrependingContent()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', "OtherStuff");
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent', true);
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertTrue($modification->isConfirmationRequired());
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent' . PHP_EOL. 'OtherStuff', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }

    /**
     * @test
     */
    public function existingContentDoesnNotRequireConfirmation()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', "FileContent" . PHP_EOL . "OtherStuff");
        $modification = new AddToFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());
        $modification->apply();
        $this->assertEquals('FileContent' . PHP_EOL. 'OtherStuff', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }
}
