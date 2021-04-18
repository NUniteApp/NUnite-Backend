<?php
function autoloadClasses($className) {
    $filename = "classes\\" . strtolower($className) . ".class.php";
    $filename = str_replace('\\', DIRECTORY_SEPARATOR, $filename);
    if (is_readable($filename)) {
        include_once $filename;
    } else {
        exit("File not found: " . $className . " (" . $filename . ")");
    }

}
spl_autoload_register("autoloadClasses");

$ini['main'] = parse_ini_file("config.ini",true);


define('BASEPATH', $ini['main']['paths']['basepath']);
define('CSSPATH', $ini['main']['paths']['css']);
define('JWTKEY', $ini['main']['jwtkey']['key']);
?>
