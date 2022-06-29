<?php

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

interface MigrationInterface
{
    /**
     * Run migration forward.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Run migration backward.
     *
     * @return void
     */
    public function down(): void;
}
