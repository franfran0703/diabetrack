<?php

session_start();

// Autoload base classes
require_once __DIR__ . '/../app/controllers/Controller.php';
require_once __DIR__ . '/../app/models/Model.php';

// Simple router — reads ?url=controller/method/param
$url = isset($_GET['url']) ? $_GET['url'] : 'home/index';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$parts = explode('/', $url);

$controllerName = isset($parts[0]) ? ucfirst($parts[0]) . 'Controller' : 'HomeController';
$method         = isset($parts[1]) ? $parts[1] : 'index';
$param          = isset($parts[2]) ? $parts[2] : null;

$controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();

    if (method_exists($controller, $method)) {
        $param ? $controller->$method($param) : $controller->$method();
    } else {
        die('Method not found: ' . $method);
    }
} else {
    die('Controller not found: ' . $controllerName);
}