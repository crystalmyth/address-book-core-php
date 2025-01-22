<?php

// Autoload classes using Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Use the Router helper
use App\Helpers\Router;

// Start a session (if needed for flash messages or session management)
session_start();

// Create a Router instance
$router = new Router();

// Handle the incoming request
$router->handleRequest();
