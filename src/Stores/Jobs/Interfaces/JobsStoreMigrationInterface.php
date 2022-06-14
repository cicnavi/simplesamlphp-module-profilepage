<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs\Interfaces;

interface JobsStoreMigrationInterface
{
    /**
     * Run this jobs store migration.
     * @return void
     */
    public function run(): void;
}
