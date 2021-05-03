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

function exceptionHandler($e) {
    $msg = array("status" => "500", "message" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine());
    $usr_msg = array("status" => "500", "message" => "Sorry! Internal server error");
    header("Access-Control-Allow-Origin: *"); 
    header("Content-Type: application/json; charset=UTF-8"); 
    header("Access-Control-Allow-Methods: GET, POST");
    echo json_encode($msg);
    logError($msg);
  }
set_exception_handler('exceptionHandler');


$ini['main'] = parse_ini_file("config.ini",true);
$ini['routes'] = parse_ini_file("routes.ini",true);

define('BASEPATH', $ini['main']['paths']['basepath']);
define('CSSPATH', $ini['main']['paths']['css']);
define('JWTKEY', $ini['main']['jwtkey']['key']);
?>
