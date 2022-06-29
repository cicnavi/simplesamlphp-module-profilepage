<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\Bases;

use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;

abstract class AbstractMigrator
{
    public function gatherMigrationClassesFromDirectory(string $directory, string $namespace): array
    {
        // Get files without dot directories
        $files = array_values(array_diff(scandir($directory), ['..', '.']));

        array_walk($files, function (string &$file) use ($namespace) {
            // Remove .php extension from filename
            $file = basename($file, '.php');
            // Add namespace for each entry
            $file = $namespace . '\\' . $file;
        });

        // Migration classes must implement MigrationInterface, so discard any other classes.
        return array_filter($files, function (string $file) {
            return is_subclass_of($file, MigrationInterface::class);
        });
    }
}
