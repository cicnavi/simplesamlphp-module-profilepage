<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

interface MigrationInterface
{
    /**
     * Run migration forward.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Run migration backward.
     *
     * @return void
     */
    public function revert(): void;
}
