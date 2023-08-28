<?php
declare(strict_types=1);

namespace Sitegeist\Noderobis\Tests\Unit\Domain\Generator;

use Neos\ContentRepository\Core\NodeType\DefaultNodeLabelGeneratorFactory;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Sitegeist\Noderobis\Domain\Generator\NodeTypeGenerator;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecification;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeNameSpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\NodeTypeSpecification;
use Sitegeist\Noderobis\Domain\Specification\OptionsSpecification;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationCollection;
use Sitegeist\Noderobis\Domain\Specification\PropertySpecificationFactory;
use Sitegeist\Noderobis\Domain\Specification\TetheredNodeSpecificationCollection;
use Sitegeist\Noderobis\Tests\Unit\BaseTestCase;
use function GuzzleHttp\Promise\queue;

class NodeTypeGeneratorTest extends BaseTestCase
{
    protected NodeTypeGenerator $generator;
    protected NodeTypeManager|MockObject $mockNodeTypeManager;
    protected DefaultNodeLabelGeneratorFactory $mockNodeLabelGeneratorFactory;

    public function setUp(): void
    {

        $this->mockNodeTypeManager = $this->createMock(NodeTypeManager::class);
        $this->mockNodeLabelGeneratorFactory = new DefaultNodeLabelGeneratorFactory();

        $this->generator = new NodeTypeGenerator();
        $this->generator->setNodeTypeManager($this->mockNodeTypeManager);
        $this->generator->injectNodeLabelGeneratorFactory( $this->mockNodeLabelGeneratorFactory);
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

        $superTypeA = new NodeType(NodeTypeName::fromString('Vendor.Package:SuperType.A'), [], [], $this->mockNodeTypeManager, $this->mockNodeLabelGeneratorFactory);
        $superTypeB = new NodeType(NodeTypeName::fromString('Vendor.Package:SuperType.B'), [], [], $this->mockNodeTypeManager, $this->mockNodeLabelGeneratorFactory);

        $this->mockNodeTypeManager->expects($this->exactly(2))
            ->method('getNodeType')
            ->withConsecutive(['Vendor.Package:SuperType.A'], ['Vendor.Package:SuperType.B'])
            ->willReturnOnConsecutiveCalls($superTypeA, $superTypeB);

        $nodetype = $this->generator->generateNodeType($specification);

        $this->assertInstanceOf(NodeType::class, $nodetype);
        $this->assertSame(['Vendor.Package:SuperType.A' => $superTypeA, 'Vendor.Package:SuperType.B' => $superTypeB], $nodetype->getDeclaredSuperTypes());
    }


    public function propertiesAreGeneratedDataProvider (): array
    {
        return [
            [
                'title',
                'string',
                null,
                [
                    'type' => 'string',
                    'ui' => [
                        'inspector' => ['group' => 'default'],
                        'label' => 'title',
                        'reloadIfChanged' => true
                    ]
                ]
            ],
            [
                'gender',
                'string',
                ['male', 'female'],
                [
                    'type' => 'string',
                    'ui' => [
                        'inspector' => [
                            'editor' => 'Neos.Neos/Inspector/Editors/SelectBoxEditor',
                            'editorOptions' => ['values' => ['male' => ['label' => 'male'], 'female' => ['label' => 'female']]],
                            'group' => 'default'
                        ],
                        'label' => 'gender',
                        'reloadIfChanged' => true
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider propertiesAreGeneratedDataProvider
     */
    public function propertiesAreGenerated(string $name, string $typeOrPreset, array $allowedValues = null, array $expectedPropertyConfiguration)
    {
        $factory = new PropertySpecificationFactory();
        $this->inject(
            $factory,
            'typeConfiguration',
            [
                'string' => ['editor' => 'Neos.Neos/Inspector/Editors/TextFieldEditor'],
                'integer' => ['editor' => 'Neos.Neos/Inspector/Editors/TextFieldEditor'],
                'boolean' => ['editor' => 'Neos.Neos/Inspector/Editors/BooleanEditor'],
                'array' => ['typeConverter' => 'Neos\\Flow\\Property\\TypeConverter\\ArrayConverter', 'editor' => 'Neos.Neos/Inspector/Editors/SelectBoxEditor']
            ]
        );

        $propertySpecification = $factory->generatePropertySpecificationFromCliInput($name, $typeOrPreset, $allowedValues);

        $specification = $this->prepareSpecification(
            name: NodeTypeNameSpecification::fromString("Vendor.Package:Example")
        );
        $specification = $specification->withProperty($propertySpecification);

        $nodetype = $this->generator->generateNodeType($specification);
        $properties = $nodetype->getProperties();

        $this->assertInstanceOf(NodeType::class, $nodetype);
        $this->assertSame("Vendor.Package:Example", $nodetype->getName());
        $this->assertArrayHasKey($name, $properties);
        $this->assertSame($expectedPropertyConfiguration, $properties[$name]);
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
