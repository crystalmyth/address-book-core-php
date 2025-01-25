<?php
namespace App\Seeds;

// Autoload classes using Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helpers\Database;
use PDO;
use PDOException;
use Exception;
use App\Seeds\CitySeeder;
use App\Seeds\ContactSeeder;
use App\Seeds\GroupSeeder;


$db = Database::getConnection();
CitySeeder::seedCities($db);
GroupSeeder::seed($db);
// ContactSeeder::seed($db);