<?php

namespace App\Models;

use App\Helpers\Database;

class City
{
    private $db;

    public function __construct()
    {
        // Initialize the database connection
        $this->db = Database::getConnection();
    }

    // Fetch all cities from the database
    public function getAll($page = 1, $limit = 10, $q='')
    {
        $query = "SELECT * FROM cities ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare($query);
        if ($q) {
            $query = "SELECT * FROM cities WHERE name LIKE :q ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($query);
            $searchTerm = '%'.$q.'%';
            $stmt->bindParam(':q', $searchTerm, \PDO::PARAM_STR);
        }
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $cities = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $counts = "SELECT COUNT(*) as total FROM cities";
        $countStmt = $this->db->prepare($counts);
        $countStmt->execute();
        $counts = $countStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            "cities" => $cities,
            "total" => $counts['total'],
            $page => $page,
            $limit => $limit
        ];
    }

    // Fetch a specific city by its ID
    public function getById($id)
    {
        $query = "SELECT * FROM cities WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getByName($name, $id=null)
    {
        if($id) {
            $query = "SELECT * FROM cities WHERE name = :name AND id <> :id";
        } else {
            $query = "SELECT * FROM cities WHERE name = :name";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        if($id) {
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        } 
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Add a new city
    public function create($name)
    {
        $query = "INSERT INTO cities (name) VALUES (:name)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Update an existing city
    public function update($id, $name)
    {
        $query = "UPDATE cities SET name = :name WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Delete a city by its ID
    public function delete($id)
    {
        $query = "DELETE FROM cities WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
