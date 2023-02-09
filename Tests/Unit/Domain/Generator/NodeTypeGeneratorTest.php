<?php
declare(strict_types=1);

namespace Sitegeist\Noderobis\Tests\Unit\Domain\Generator;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\OptionsSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationCollection;

class NodeTypeGeneratorTest extends TestCase
{
    protected NodeTypeGenerator $generator;
    protected NodeTypeManager|MockObject $mockNodeTypeManager;

    public function setUp(): void
    {
        $this->mockNodeTypeManager = $this->createMock(NodeTypeManager::class);

        $this->generator = new NodeTypeGenerator();
        $this->generator->injectNodeTypeManager($this->mockNodeTypeManager);
    }

    /**
     * @test
     */
    public function nodeTypesAreGenerated()
    {
        $specification = $this->prepareSpecification(
            name: NodeTypeNameSpecification::fromString("Vendor.Package:Example")
        );

        $nodetype = $this->generator->generateNodeType($specification);

        $this->assertInstanceOf(NodeType::class, $nodetype);
        $this->assertFalse($nodetype->isAbstract());
        $this->assertSame("Vendor.Package:Example", $nodetype->getName());
    }

    /**
     * @test
     */
    public function nodeTypesAreGeneratedAbstract()
    {
        $specification = $this->prepareSpecification(
            name: NodeTypeNameSpecification::fromString("Vendor.Package:Example"),
            abstract: true
        );

        $nodetype = $this->generator->generateNodeType($specification);

        $this->assertInstanceOf(NodeType::class, $nodetype);
        $this->assertTrue($nodetype->isAbstract());
        $this->assertSame("Vendor.Package:Example", $nodetype->getName());
    }

    /**
     * @test
     */
    public function superTypesAreAssigned()
    {
        $specification = $this->prepareSpecification(
            name: NodeTypeNameSpecification::fromString("Vendor.Package:Example"),
            superTypes: NodeTypeNameSpecificationCollection::fromStringArray(['Vendor.Package:SuperType.A', 'Vendor.Package:SuperType.B'])
        );

        $superTypeA = new NodeType('Vendor.Package:SuperType.A', [], []);
        $superTypeB = new NodeType('Vendor.Package:SuperType.B', [], []);

        $this->mockNodeTypeManager->expects($this->exactly(2))
            ->method('getNodeType')
            ->withConsecutive(['Vendor.Package:SuperType.A'], ['Vendor.Package:SuperType.B'])
            ->willReturnOnConsecutiveCalls($superTypeA, $superTypeB);

        $nodetype = $this->generator->generateNodeType($specification);

        $this->assertInstanceOf(NodeType::class, $nodetype);
        $this->assertSame([$superTypeA, $superTypeB], $nodetype->getDeclaredSuperTypes());
    }

    /**
     * @test
     */
    public function optionsAreAssigned()
    {
        $specification = $this->prepareSpecification();

        $optionSpecification = new OptionsSpecification(['foo' => 'bar', 'test' => ['test1', 'test2']]);
        $specification = $specification->withOptionsSpecification($optionSpecification);

        $nodeType = $this->generator->generateNodeType($specification);

        $this->assertInstanceOf(NodeType::class, $nodeType);
        $this->assertSame($optionSpecification->options, $nodeType->getOptions());
    }

    /**
     * @test
     */
    public function missingSuperTypesYieldNodeTypeNotFoundException()
    {
        $specification = $this->prepareSpecification(
            name: NodeTypeNameSpecification::fromString("Vendor.Package:Example"),
            superTypes: NodeTypeNameSpecificationCollection::fromStringArray(['Vendor.Package:SuperType'])
        );

        $this->mockNodeTypeManager->expects($this->once())
            ->method('getNodeType')
            ->with('Vendor.Package:SuperType')
            ->willThrowException(new NodeTypeNotFoundException());

        $this->expectException(NodeTypeNotFoundException::class);
        $this->generator->generateNodeType($specification);
    }

    protected function prepareSpecification(
        NodeTypeNameSpecification $name = null,
        NodeTypeNameSpecificationCollection $superTypes = null,
        PropertySpecificationCollection $nodeProperties = null,
        TetheredNodeSpecificationCollection $tetheredNodes = null,
        bool $abstract = null,
    ): NodeTypeSpecification {
        return new NodeTypeSpecification(
            $name ?? NodeTypeNameSpecification::fromString("Vendor.Package:Example"),
            $superTypes ??NodeTypeNameSpecificationCollection::fromStringArray([]),
            $nodeProperties ?? new PropertySpecificationCollection(),
            $tetheredNodes ?? new TetheredNodeSpecificationCollection(),
            $abstract ?? false,
            null,
            null,
            null
        );
    }
}
