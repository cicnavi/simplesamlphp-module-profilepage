<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use SimpleSAML\Module\accounting\Services\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\Logger
 */
class LoggerServiceTest extends TestCase
{
    public function testCanCallAllMethods(): void
    {
        $loggerService = new Logger();

        $loggerService->stats('test');
        $loggerService->debug('test');
        $loggerService->info('test');
        $loggerService->notice('test');
        $loggerService->warning('test');
        $loggerService->error('test');
        $loggerService->alert('test');
        $loggerService->critical('test');
        $loggerService->emergency('test');

        $loggerService->emergency('test', ['sample' => 'context']);

        $this->assertTrue(true); // Nothing to evaluate
    }
}
