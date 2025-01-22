<?php
namespace App\Seeds;

// Autoload classes using Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helpers\Database;
use PDO;
use PDOException;
use Exception;
$db = Database::getConnection();

$page_no = 0;

$apiUrls = [];
for ($i=0; $i < 10; $i++) { 
    array_push($apiUrls, "https://jsonplaceholder.typicode.com/users?page=".++$page_no);
}

// Initialize a multi-handle
$multiHandle = curl_multi_init();
$curlHandles = [];

// Add each URL to the multi-handle
foreach ($apiUrls as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = $ch;
}

// Execute all requests simultaneously
do {
    $status = curl_multi_exec($multiHandle, $active);
    if ($active) {
        // Wait for activity on any curl connection
        curl_multi_select($multiHandle);
    }
} while ($active && $status == CURLM_OK);

// Collect responses
$responses = array();
foreach ($curlHandles as $ch) {
    $responses[] = curl_multi_getcontent($ch);
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

// Close the multi-handle
curl_multi_close($multiHandle);


try {
    // Start transaction
    $db->beginTransaction();

    $users = [];
    $cities = [];
    $selectedUsers = [];
    // Process responses
    foreach ($responses as $index => $response) {        
        foreach (json_decode($response, true) as $user) {
            $users[] = $user;
            if(isset($user["address"]["city"]) && !in_array($user["address"]["city"], $cities)){
                array_push($cities, $user["address"]["city"]);
            }
        }
    }

    $index = 0;
    foreach ($cities as $city) {
        $sql = "INSERT INTO cities (name) VALUES (:name)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $city);
        $stmt->execute();

        echo "City Name: ".$city." created successfully !! \n";

        foreach($users as $user){
            if(!in_array($user["name"], $selectedUsers)){
                array_push($selectedUsers, $user["name"]);
                $cities_sql = "SELECT * FROM cities WHERE name = :name";
                $cities_stmt = $db->prepare($cities_sql);
                $cities_stmt->bindParam(':name', $city);
                $cities_stmt->execute();
                $cityData = $cities_stmt->fetch(PDO::FETCH_OBJ);
                if($cityData){
                    $sql = "INSERT INTO address_book (name, phone, email, zipcode, street, city_id) VALUES (:name, :phone, :email, :zipcode, :street, :city_id)";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':name', $user["name"]);
                    $phone = preg_replace('/\D/', '', $user["phone"]);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':email', $user["email"]);
                    $stmt->bindParam(':zipcode', $user["address"]["zipcode"]);
                    $stmt->bindParam(':street', $user["address"]["street"]);
                    $stmt->bindParam(':city_id', $cityData->id);
                    $stmt->execute();
                }
                
                echo "User: ".$user["name"]." created successfully !! \n";   
            }
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
