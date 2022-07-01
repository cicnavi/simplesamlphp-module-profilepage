<?php

namespace SimpleSAML\Test\Module\accounting\Services;

use SimpleSAML\Module\accounting\Services\LoggerService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\LoggerService
 */
class LoggerServiceTest extends TestCase
{
    public function testCanCallAllMethods(): void
    {
        $loggerService = new LoggerService();

        $loggerService->stats('test');
        $loggerService->debug('test');
        $loggerService->info('test');
        $loggerService->notice('test');
        $loggerService->warning('test');
        $loggerService->error('test');
        $loggerService->alert('test');
        $loggerService->critical('test');
        $loggerService->emergency('test');

        $this->assertTrue(true); // Nothing to evaluate
    }
}
