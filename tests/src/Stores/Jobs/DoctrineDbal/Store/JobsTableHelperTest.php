<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

use Doctrine\DBAL\Schema\Table;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\JobsTableHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\JobsTableHelper
 */
class JobsTableHelperTest extends TestCase
{
    public function testCanPrepareJobsTable(): void
    {
        $table = JobsTableHelper::prepareJobsTable('test');

        $this->assertInstanceOf(Table::class, $table);
        $this->assertSame('test', $table->getName());
    }

    public function testPrepareJobsTableThrowsForInvalidTableName(): void
    {
        $this->expectException(StoreException::class);
        JobsTableHelper::prepareJobsTable('');
    }
}
