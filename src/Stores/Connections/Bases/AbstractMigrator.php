<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Connections\Bases;

use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;
use SimpleSAML\Module\accounting\Exceptions\MigrationException;
use SimpleSAML\Module\accounting\Helpers\FilesystemHelper;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;

abstract class AbstractMigrator
{
    public const DEFAULT_MIGRATIONS_DIRECTORY_NAME = 'Migrations';

    /**
     * @param string $directory
     * @param string $namespace
     * @return class-string[]
     */
    public function gatherMigrationClassesFromDirectory(string $directory, string $namespace): array
    {
        $directory = FilesystemHelper::getRealPath($directory);

        // Get files without dot directories
        $files = array_values(array_diff(scandir($directory), ['..', '.']));

        array_walk($files, function (string &$file) use ($namespace) {
            // Remove .php extension from filename
            $file = basename($file, '.php');
            // Prepend namespace for each entry
            $file = $namespace . '\\' . $file;
        });

        // Migration classes must follow proper interfaces, so do validate each of them and discard invalid ones.
        /** @var class-string[] $migrationClasses */
        $migrationClasses = array_filter($files, function (string $file) {
            try {
                $this->validateMigrationClass($file);
                return true;
            } catch (InvalidValueException $exception) {
                return false;
            }
        });

        return $migrationClasses;
    }

    /**
     * @param class-string[] $migrationClasses
     * @return void
     */
    public function runMigrationClasses(array $migrationClasses): void
    {
        foreach ($migrationClasses as $migrationClass) {
            $this->validateMigrationClass($migrationClass);

            $migration = $this->buildMigrationClassInstance($migrationClass);

            try {
                $migration->run();
                $this->markImplementedMigration($migrationClass);
            } catch (\Throwable $exception) {
                $message = sprintf(
                    'Could not run migration class %s. Error was: %s',
                    $migrationClass,
                    $exception->getMessage()
                );

                throw new MigrationException($message);
            }
        }
    }

    public function validateMigrationClass(string $migrationClass): void
    {
        if (! is_subclass_of($migrationClass, MigrationInterface::class)) {
            throw new InvalidValueException(
                sprintf('Migration class does not implement MigrationInterface (%s)', $migrationClass)
            );
        }
    }

    /**
     * @param class-string $migrationClass
     * @return MigrationInterface
     */
    abstract protected function buildMigrationClassInstance(string $migrationClass): MigrationInterface;

    /**
     * @param class-string $migrationClass
     * @return void
     */
    abstract protected function markImplementedMigration(string $migrationClass): void;
}
