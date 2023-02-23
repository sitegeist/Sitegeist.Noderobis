<?php
declare(strict_types=1);

namespace Sitegeist\Noderobis\Tests\Unit\Domain\Modification;

use Sitegeist\Noderobis\Domain\Modification\ModificationCollection;
use Sitegeist\Noderobis\Domain\Modification\ModificationInterface;
use Sitegeist\Noderobis\Tests\Unit\BaseTestCase;

class ModificationCollectionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function emptyCollectionDoesNotNeedConfirmations()
    {
        $collection = new ModificationCollection();
        $this->assertFalse($collection->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function confirmationIsNotNeededIfNoModificationsNeedsIt()
    {
        $modification1 = $this->createMock(ModificationInterface::class);
        $modification2 = $this->createMock(ModificationInterface::class);
        $modification3 = $this->createMock(ModificationInterface::class);

        $collection = new ModificationCollection(
            $modification1,
            $modification2,
            $modification3
        );

        $modification1->expects($this->once())->method('isConfirmationRequired')->willReturn(false);
        $modification2->expects($this->once())->method('isConfirmationRequired')->willReturn(false);
        $modification3->expects($this->once())->method('isConfirmationRequired')->willReturn(false);

        $this->assertFalse($collection->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function ifASubModificationRequiresConformationTheCollectionDoesAswell()
    {
        $modification1 = $this->createMock(ModificationInterface::class);
        $modification2 = $this->createMock(ModificationInterface::class);
        $modification3 = $this->createMock(ModificationInterface::class);

        $collection = new ModificationCollection(
            $modification1,
            $modification2,
            $modification3
        );

        $modification1->expects($this->once())->method('isConfirmationRequired')->willReturn(false);
        $modification2->expects($this->once())->method('isConfirmationRequired')->willReturn(true);
        $modification3->expects($this->never())->method('isConfirmationRequired')->willReturn(false);

        $this->assertTrue($collection->isConfirmationRequired());
    }

    /**
     * @test
     */
    public function applyWillCallApplyOnAllSubModifications()
    {
        $modification1 = $this->createMock(ModificationInterface::class);
        $modification2 = $this->createMock(ModificationInterface::class);
        $modification3 = $this->createMock(ModificationInterface::class);

        $collection = new ModificationCollection(
            $modification1,
            $modification2,
            $modification3
        );

        $modification1->expects($this->once())->method('apply');
        $modification2->expects($this->once())->method('apply');
        $modification3->expects($this->once())->method('apply');

        $collection->apply();
    }
}
