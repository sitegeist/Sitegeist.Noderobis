<?php

declare(strict_types=1);

namespace Sitegeist\Nodemerobis\Tests\Utility;

use PHPUnit\Framework\TestCase;
use Sitegeist\Nodemerobis\Utility\ConfigurationUtility;

class ConfigurationUtilityTest extends TestCase
{

    public function provideDataForFindConfigurationPathesByKeyWorks(): array
    {
        return [
            [ [], [] ],
            [ ['foo' => ['somethig' => 'string']], [] ],
            [ ['foo' => ['type' => 'string']], ['foo'] ],
            [ ['foo' => ['bar' => ['type' => 'string']]], ['foo.bar'] ],
            [ ['foo' => ['bar' => ['baz' => ['type' => 'string']]]], ['foo.bar.baz'] ],
            [ ['foo' => ['bar' => ['type' => 'string']], 'baz' => ['type' => 'string']], ['foo.bar', 'baz'] ],
        ];
    }

    /**
     * @test
     * @dataProvider provideDataForFindConfigurationPathesByKeyWorks
     */
    public function findConfigurationPathesByKeyWorks(array $input, array $expectation): void
    {
        $this->assertSame(
            $expectation,
            ConfigurationUtility::findConfigurationPathesByKey($input, 'type')
        );
    }
}
