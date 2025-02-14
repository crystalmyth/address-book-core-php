<?php

namespace App\Helpers;

use App\Controllers\ContactController;
use App\Controllers\CityController;
use App\Controllers\GroupController;

class Router
{
    public static function routes()
    {
        return [
            '/' => [
                'controller' => ContactController::class,
                'method' => 'index',
            ],
            '/create' => [
                'controller' => ContactController::class,
                'method' => 'create',
            ],
            '/edit' => [
                'controller' => ContactController::class,
                'method' => 'edit',
            ],
            '/delete' => [
                'controller' => ContactController::class,
                'method' => 'delete',
            ],
            '/export' => [
                'controller' => ContactController::class,
                'method' => 'export',
            ],
            // Routes for Cities
            '/cities' => [
                'controller' => CityController::class,
                'method' => 'index',  // Show all cities
            ],
            '/cities/create' => [
                'controller' => CityController::class,
                'method' => 'create',  // Show form to create a new city
            ],
            '/cities/edit' => [
                'controller' => CityController::class,
                'method' => 'edit',  // Show form to edit a city
            ],
            '/cities/delete' => [
                'controller' => CityController::class,
                'method' => 'delete',  // Handle city deletion
            ],
            // Routes for Groups
            '/groups' => [
                'controller' => GroupController::class,
                'method' => 'index',  // Show all groups
            ],
            '/groups/create' => [
                'controller' => GroupController::class,
                'method' => 'create',  // Show form to create a new city
            ],
            '/groups/edit' => [
                'controller' => GroupController::class,
                'method' => 'edit',  // Show form to edit a city
            ],
            '/groups/delete' => [
                'controller' => GroupController::class,
                'method' => 'delete',  // Handle city deletion
            ],
        ];
    }

    public function handleRequest()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $routes = self::routes();

        if (isset($routes[$path])) {
            $route = $routes[$path];
            $controllerClass = $route['controller'];
            $method = $route['method'];


            $controller = new $controllerClass();

            // Check if the method requires parameters (e.g., 'edit')
            if ($method === 'edit' || $method === 'delete') {
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $controller->$method($id);
                } else {
                    $this->handleNotFound();
                }
            } else {
                $controller->$method();
            }
        } else {
            $this->handleNotFound();
        }
    }

    private function handleNotFound()
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>";
        exit;
    }
}
