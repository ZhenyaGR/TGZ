<?php

namespace ZhenyaGR\TGZ;

//Динамическое подключение классов, только при его использовании
spl_autoload_register(static function ($class) {
    if (str_starts_with($class, 'ZhenyaGR\\TGZ')) {
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $class = str_replace('ZhenyaGR\\TGZ\\', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = $baseDir . "$class.php";
        if (file_exists($file)) {
            require $file;
        }
    }
});