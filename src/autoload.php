<?php
/**
 * This file is part of the avadim\Process package
 * https://github.com/aVadim483/Process
 */

spl_autoload_register(static function ($class) {
    $namespace = 'avadim\\Process\\';
    if (0 === strpos($class, $namespace)) {
        $file = __DIR__ . str_replace($namespace, '/',  $class) . '.php';
        include str_replace('\\', '/', $file);
    }
});

// EOF