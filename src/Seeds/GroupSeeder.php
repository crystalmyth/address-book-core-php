<?php

namespace App\Seeds;

use App\Helpers\Database;
use PDO;
use PDOException;

class GroupSeeder {
    private static $groups = [
        'D', 'C', 'A', 'AA', 'B' 
    ];

    private static function seedGroupConnections(PDO $db, array $group_hierarchy)
    {
        foreach ($group_hierarchy as $parent => $children) {
            $parentId = self::getGroupIdByName($parent);

            if(!empty($children)) {
                foreach ($children as $child => $grand_children) {
                    $childId = self::getGroupIdByName($child);
    
                    $gcStmt = "INSERT INTO group_connections (parent_group_id, child_group_id) VALUES (:parent_group_id, :child_group_id)";
                    $gcStmt = $db->prepare($gcStmt);
                    $gcStmt->bindParam(':parent_group_id', $parentId, \PDO::PARAM_INT);
                    $gcStmt->bindParam(':child_group_id', $childId, \PDO::PARAM_INT);
                    $gcStmt->execute();
    
                    self::seedGroupConnections($db, $grand_children); 
                }
            }
        }
    }

    private static function getGroupIdByName($parent)
    {
        $groups = self::$groups;
        return array_search($parent, $groups) + 1;
    }

    public static function seed(PDO $db)
    {
        try {
            $db->beginTransaction();

            $groups = self::$groups;

            // create groups
            foreach ($groups as $groupName) {
                $groupStmt = "INSERT INTO groups (name) VALUES (:name)";
                $groupStmt = $db->prepare($groupStmt);
                $groupStmt->bindParam(':name', $groupName, \PDO::PARAM_STR);
                $groupStmt->execute();
            }

            $groupConnections = [
                'D' => [
                    'C' => ['A'],
                    'AA' => [],
                    'B' => []
                ],
            ];

            // create group connections
            self::seedGroupConnections($db, $groupConnections);

            $db->commit();
            echo "groups migrations applied successfully.\n";

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error applying groups migration: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}