<?php
/**
 * phpDocumentor
 *
 * PHP Version 5.3
 *
 * @copyright 2010-2018 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Compiler\Pass;

use Mockery as m;

/**
 * Tests the functionality for the Debug Pass
 */
class DebugTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{

    /**
     * @covers \phpDocumentor\Compiler\Pass\Debug::execute
     */
    public function testLogDebugAnalysis() : void
    {
        $testString = 'test';
        $projectDescriptorMock = m::mock('phpDocumentor\Descriptor\ProjectDescriptor');

        $loggerMock = m::mock('Psr\Log\LoggerInterface')
            ->shouldReceive('debug')->with($testString)
            ->getMock();

        $analyzerMock = m::mock('phpDocumentor\Descriptor\ProjectAnalyzer')
            ->shouldReceive('analyze')->with($projectDescriptorMock)
            ->shouldReceive('__toString')->andReturn($testString)
            ->getMock();

        $fixture = new Debug($loggerMock, $analyzerMock);
        $fixture->execute($projectDescriptorMock);

        $this->assertTrue(true);
    }

    /**
     * @covers \phpDocumentor\Compiler\Pass\Debug::getDescription
     */
    public function testGetDescription() : void
    {
        $debug = new Debug(m::mock('Psr\Log\LoggerInterface'), m::mock('phpDocumentor\Descriptor\ProjectAnalyzer'));
        $expected = 'Analyze results and write report to log';
        $this->assertSame($expected, $debug->getDescription());
    }
}
