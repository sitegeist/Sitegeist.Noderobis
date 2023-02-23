<?php
declare(strict_types=1);

namespace Sitegeist\Noderobis\Tests\Unit\Domain\Modification;

use org\bovigo\vfs\vfsStream;
use Sitegeist\Noderobis\Domain\Modification\WriteFileModification;
use Sitegeist\Noderobis\Tests\Unit\BaseTestCase;

class WriteFileModificationTest extends BaseTestCase
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
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileDoesNotExist('vfs://Test/Directory/ExampleFile.txt');
        $this->assertFalse($modification->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function existingFilesRequireConfirmationIfContentIsNotPresent()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', "OtherStuff");
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertTrue($modification->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function contentIsWrittenToSpecifiedLocation()
    {
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileDoesNotExist('vfs://Test/Directory/ExampleFile.txt');
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }

    /**
     * @test
     */
    public function existingFilesAreOverwritten()
    {
        file_put_contents('vfs://Test/Directory/ExampleFile.txt', 'OtherStuff');
        $modification = new WriteFileModification('vfs://Test/Directory/ExampleFile.txt', 'FileContent');
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $modification->apply();
        $this->assertFileExists('vfs://Test/Directory/ExampleFile.txt');
        $this->assertEquals('FileContent', file_get_contents('vfs://Test/Directory/ExampleFile.txt'));
    }
}
