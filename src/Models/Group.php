<?php

namespace App\Models;

use App\Helpers\Database;

class Group
{
    private $db;

    public function __construct()
    {
        // Initialize the database connection
        $this->db = Database::getConnection();
    }

    // Fetch all groups from the database
    public function getAll($page = 1, $limit = 10, $q='')
    {
        $query = "
            SELECT 
                g.id, 
                g.name, 
                GROUP_CONCAT(p.name SEPARATOR ', ') AS parent_group_names, 
                GROUP_CONCAT(p.id SEPARATOR ', ') AS parent_ids 
            FROM 
                groups g
            LEFT JOIN 
                group_connections gc ON g.id = gc.child_group_id
            LEFT JOIN 
                groups p ON gc.parent_group_id = p.id
            WHERE (
                (:q IS NOT NULL AND (
                    g.name LIKE :q
                    OR p.name LIKE :q
                ))
            )
            GROUP BY 
                g.id
        ";
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare($query);
        $searchTerm = '%'.$q.'%';
        $stmt->bindParam(':q', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $counts = "SELECT COUNT(*) as total FROM groups";
        $countStmt = $this->db->prepare($counts);
        $countStmt->execute();
        $counts = $countStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            "groups" => $groups,
            "total" => $counts['total'],
            $page => $page,
            $limit => $limit
        ];
    }

    // Fetch a specific group by its ID
    public function getById($id)
    {
        $query = "
            SELECT 
                g.id, 
                g.name, 
                GROUP_CONCAT(p.id SEPARATOR ', ') AS parent_ids 
            FROM 
                groups g
            LEFT JOIN 
                group_connections gc ON g.id = gc.child_group_id
            LEFT JOIN 
                groups p ON gc.parent_group_id = p.id
            WHERE 
                g.id = :id
            GROUP BY 
                g.id;
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getByName($name, $id=null)
    {
        if($id) {
            $query = "SELECT * FROM groups WHERE name = :name AND id <> :id";
        } else {
            $query = "SELECT * FROM groups WHERE name = :name";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        if($id) {
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        } 
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    // Add a new group
    public function create($name, $parent_id)
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO groups (name) VALUES (:name)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->execute();

            $group_id = $this->db->lastInsertId();

            foreach ($parent_id as $pid) {
                $gc_stmt = "INSERT INTO group_connections (parent_group_id, child_group_id) VALUES (:parent_group_id, :child_group_id)";
                $gc_stmt = $this->db->prepare($gc_stmt);
                $gc_stmt->bindParam(':parent_group_id', $pid, \PDO::PARAM_INT);
                $gc_stmt->bindParam(':child_group_id', $group_id, \PDO::PARAM_INT);
                $gc_stmt->execute();
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error create group: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    // Update an existing group
    public function update($id, $name, $parent_id)
    {
        try {
            $this->db->beginTransaction();

            $query = "UPDATE groups SET name = :name WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            //deleting previous connections of the group:id
            $query = "DELETE FROM group_connections WHERE child_group_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            foreach ($parent_id as $pid) {
                $gc_stmt = "INSERT INTO group_connections (parent_group_id, child_group_id) VALUES (:parent_group_id, :child_group_id)";
                $gc_stmt = $this->db->prepare($gc_stmt);
                $gc_stmt->bindParam(':parent_group_id', $pid, \PDO::PARAM_INT);
                $gc_stmt->bindParam(':child_group_id', $id, \PDO::PARAM_INT);
                $gc_stmt->execute();
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error update group: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    // Delete a group by its ID
    public function delete($id)
    {
        try {
            $query = "DELETE FROM groups WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            //deleting previous connections of the group:id
            $query = "DELETE FROM group_connections WHERE child_group_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo "Error delete group: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }


    public function get_contacts_by_group_id($id)
    {
        try {
            $query = "
                SELECT 
                    c.*,
                    ct.name AS city,
                    gc.inherited AS inherited 
                FROM 
                    contacts c
                LEFT JOIN
                    cities ct
                ON
                    c.city_id = ct.id
                LEFT JOIN
                    group_contacts gc
                ON
                    c.city_id = gc.contact_id AND gc.group_id = :groupId
                WHERE 
                    c.id IN (SELECT contact_id FROM group_contacts WHERE group_id = :groupId)
                GROUP BY
                    c.id
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':groupId', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Failed to get contacts for group: " . $e->getMessage(), 0, $e);
        }
    }
}
