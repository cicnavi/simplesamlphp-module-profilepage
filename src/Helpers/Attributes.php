<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

class Attributes
{
    /**
     * Map files which translate attribute names to (more) user-friendly format.
     */
    final public const MAP_FILES_TO_NAME = ['facebook2name.php', 'linkedin2name.php', 'oid2name.php', 'openid2name.php',
        'removeurnprefix.php', 'twitter2name.php', 'urn2name.php', 'windowslive2name.php'];

    public function getMergedAttributeMapForFiles(string $sspBaseDirectory, array $mapFiles): array
    {
        // This is the variable name used in map files. It is set to empty array by default, but later populated
        // by each include of the map file.
        $attributemap = [];

        $fullAttributeMap = [];

        /** @var string $mapFile */
        foreach ($mapFiles as $mapFile) {
            $mapFilePath = $sspBaseDirectory . 'attributemap' . DIRECTORY_SEPARATOR . $mapFile;
            if (! file_exists($mapFilePath)) {
                continue;
            }
            include $mapFilePath;
            $fullAttributeMap = array_merge($fullAttributeMap, $attributemap);
        }

        return $fullAttributeMap;
    }
}
