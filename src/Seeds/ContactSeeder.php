<?php

namespace App\Seeds;
use App\Helpers\AsyncApi;

class ContactSeeder {
    private static function generate_random_tags()
    {
        $tags = [
            "Work", "Home", "Friends", "Family", "School", "Travel", "Hobbies", "Sports", 
            "Music", "Movies", "Books", "Food", "Shopping", "Tech", "Nature", "Art", "Games"
        ];
        $num_tags = rand(2, count($tags));
        $random_tags = array_map(function($t) use ($tags) {
            return $tags[$t];
        }, array_rand($tags, $num_tags));

        return $random_tags;
    }

    private static function createContacts($db, $user, $cityData)
    {
        $sql = "INSERT INTO contacts (name, phone, email, zipcode, street, city_id) VALUES (:name, :phone, :email, :zipcode, :street, :city_id)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $user["name"]);
        $phone = rand(1111111111, 99999999999);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $user["email"]);
        $stmt->bindParam(':zipcode', rand(111111, 999999));
        $stmt->bindParam(':street', $user["address"]["street"]);
        $stmt->bindParam(':city_id', $cityData['id']);
        $stmt->execute();
        $contact_id = $db->lastInsertId();

        echo "User: ".$user["name"]." created successfully !! \n";   
        
        return $contact_id;
    }

    public static function createContactTags($db, $user, $contact_id, $tag) {
        $tag_sql = "INSERT INTO contact_tags (name, contact_id) VALUES (:name, :contact_id)";
        $tag_stmt = $db->prepare($tag_sql);
        $tag_stmt->bindParam(':name', $tag);
        $tag_stmt->bindParam(':contact_id', $contact_id);
        $tag_stmt->execute();
        echo "Tag: ".$tag." created for user: ".$user["name"].", successfully !! \n";   
        return true;
    }

    public static function createGroupContacts($db, $contact_id, $group_id, $inherited = FALSE) 
    {
        $parent_id = self::getParentId($db, $group_id);
        if($parent_id) {
            self::createGroupContacts($db, $contact_id, $parent_id, TRUE);
        }
        
        $sql = "INSERT INTO group_contacts (group_id, contact_id, inherited) VALUES (:group_id, :contact_id, :inherited)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':group_id', $group_id, \PDO::PARAM_INT);
        $stmt->bindParam(':contact_id', $contact_id, \PDO::PARAM_INT);
        $stmt->bindParam(':inherited', $inherited, \PDO::PARAM_BOOL);
        $stmt->execute();

        return true;
    }

    public static function getParentId($db, $group_id) {
        $query = "
            SELECT 
                g.id, 
                g.name, 
                p.id AS parent_id
            FROM 
                groups g
            LEFT JOIN 
                group_connections gc ON g.id = gc.child_group_id
            LEFT JOIN 
                groups p ON gc.parent_group_id = p.id
            WHERE
                g.id = :group_id
            LIMIT 1
        ";
        $sql = $db->prepare($query);
        $sql->bindParam(':group_id', $group_id, \PDO::PARAM_INT);
        $sql->execute();
        $result = $sql->fetch(\PDO::FETCH_ASSOC);
        return $result['parent_id'];
    }

    public static function seed(\PDO $db)
    {
        try {
            $db->beginTransaction();

            $responses = AsyncApi::getData();
            $users = [];
            $selectedUsers = [];
            
            // Process responses
            foreach ($responses as $index => $response) {        
                foreach (json_decode($response, true) as $user) {
                    $users[] = $user;
                }
            }

            // Get all cities
            $cities_stmt = $db->prepare("SELECT * FROM cities");
            $cities_stmt->execute();
            $cities = $cities_stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get groups total
            $groups_stmt = $db->prepare("SELECT COUNT(*) as total FROM groups");
            $groups_stmt->execute();
            $group_total = $groups_stmt->fetch(\PDO::FETCH_ASSOC)['total'];


            foreach ($cities as $cityData) {
                foreach ($users as $user) {
                    if(!in_array($user["name"], $selectedUsers)){
                        array_push($selectedUsers, $user["name"]);
                        $contact_id = self::createContacts($db, $user, $cities[rand(1, count($cities) - 1)]);
                        print_r($contact_id." \n");
                        foreach(self::generate_random_tags() as $tag) {
                            self::createContactTags($db, $user, $contact_id, $tag); 
                        }

                        $group_id = rand(1, $group_total);
                        self::createGroupContacts($db, $contact_id, $group_id, FALSE);

                    }
                }
            }
            $db->commit();
            echo "contacts migrations applied successfully.\n";

        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error applying contacts migration: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}