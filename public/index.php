<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Direktang i-require ang Lang.php dahil ang function na t() ay hindi
// class - hindi ito mahahanap ng autoloader (na batay lang sa class name).
require_once __DIR__ . '/../app/helpers/Lang.php';

// Autoloader - naghahanap ng class files sa app/controllers, app/models, app/views
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/views/',
        __DIR__ . '/../app/helpers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Kunin ang URL path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/sitrass/public', '', $path);
$segments = explode('/', trim($path, '/'));

$controller = isset($segments[0]) && $segments[0] ? $segments[0] : 'home';
$action = isset($segments[1]) && $segments[1] ? $segments[1] : 'index';
$action = lcfirst(str_replace('-', '', ucwords($action, '-')));
$params = array_slice($segments, 2);

$controllerName = ucfirst($controller) . 'Controller';

if (class_exists($controllerName)) {
    $controllerInstance = new $controllerName();
    if (method_exists($controllerInstance, $action)) {
        call_user_func_array([$controllerInstance, $action], $params);
    } else {
        echo "404 Action Not Found";
    }
} else {
    echo "404 Controller Not Found";
}