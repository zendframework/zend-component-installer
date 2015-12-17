<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

chdir(__DIR__ . '/../');
$fallbackUrl = 'https://github.com/box-project/box2/releases/download/2.6.0/box-2.6.0.phar';

if (! is_file($argv[1])) {
    return $fallbackUrl;
}

$manifestJson = file_get_contents($argv[1]);
$files = json_decode($manifestJson, true);

if (! is_array($files)) {
    return $fallbackUrl;
}

foreach ($files as $file) {
    if (! is_array($file['version']) || ! isset($file['version'])) {
        continue;
    }

    if (version_compare($file['version'], '2.6.0', 'gte')) {
        return $file['url'];
    }
}

return $fallbackUrl;
