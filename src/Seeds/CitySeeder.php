<?php

namespace App\Seeds;

use App\Helpers\Database;
use PDO;
use PDOException;
use App\Helpers\AsyncApi;

class CitySeeder {
    public static function seedCities(PDO $db)
    {
        try {
            $db->beginTransaction();

            $cities = [];
            $users = [];
            $responses = AsyncApi::getData();
            
            // Process responses
            foreach ($responses as $index => $response) {        
                foreach (json_decode($response, true) as $user) {
                    $users[] = $user;
                    if(isset($user["address"]["city"]) && !in_array($user["address"]["city"], $cities)){
                        array_push($cities, $user["address"]["city"]);
                    }
                }
            }

            foreach($cities as $city) {
                $sql = "INSERT INTO cities (name) VALUES (:name)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':name', $city);
                $stmt->execute();

                echo "City Name: ".$city." created successfully !! \n";
            }
            $db->commit();
            echo "cities migrations applied successfully.\n";

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error applying cities migration: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}