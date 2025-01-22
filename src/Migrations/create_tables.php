<?php

// Autoload classes using Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helpers\Database;

// Get database connection
$db = Database::getConnection();

// Get all SQL files in the Migrations folder
$migrationsFolder = __DIR__;
$migrationFiles = glob($migrationsFolder . '/*.sql');

try {
    // Start transaction
    $db->beginTransaction();

    // Loop through each migration file and execute the SQL
    foreach ($migrationFiles as $file) {
        echo "Executing migration: " . basename($file) . "<br>";
        $sql = file_get_contents($file);
        
        if ($sql) {
            $db->exec($sql);
            echo "Migration executed successfully: " . basename($file) . "<br>";
        } else {
            echo "Failed to read SQL from: " . basename($file) . "<br>";
            // Rollback if we can't read the SQL file
            $db->rollBack();
            throw new Exception("Failed to read SQL from: " . basename($file));
        }
    }

    // Commit the transaction after all migrations are applied
    $db->commit();
    echo "All migrations applied successfully.<br>";

} catch (PDOException $e) {
    // Rollback transaction if something goes wrong
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error applying migrations: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    // Catch any other exceptions
    echo "Error: " . $e->getMessage() . "<br>";
}
