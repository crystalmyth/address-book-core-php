<?php

namespace App\Models;

use App\Helpers\Database;
use App\Helpers\Export;
class AddressBook
{
    private $db;

    public function __construct()
    {
        // Initialize the database connection
        $this->db = Database::getConnection();
    }

    //Fetch filter address from the database
    public function search($page = 1, $limit = 10, $q='') {
        $counts = "SELECT COUNT(*) as total FROM address_book";
        $countStmt = $this->db->prepare($counts);
        $countStmt->execute();
        $counts = $countStmt->fetch(\PDO::FETCH_ASSOC);

        $offset = ($page - 1) * $limit;
        
        $sql = "
        SELECT 
                address_book.*, 
                cities.name AS city_name
            FROM address_book
            LEFT JOIN cities ON address_book.city_id = cities.id
            WHERE address_book.name LIKE :q
            OR address_book.email LIKE :q
            OR address_book.phone LIKE :q
            OR cities.name LIKE :q
            LIMIT :offset, :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $searchTerm = '%' . $q . '%';
        $stmt->bindParam(':q', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        
        $addressBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'addresses' => $addressBooks,
            'total' => $counts['total'],
            'limit' => $limit,
            'page' => $page
        ];
    }

    // Fetch all addresses from the database
    public function getAll($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $addressBooks = "
            SELECT 
                address_book.*, 
                cities.name AS city_name
            FROM address_book
            LEFT JOIN cities ON address_book.city_id = cities.id
            ORDER BY address_book.id DESC
            LIMIT :offset, :limit
        ";

        $stmt = $this->db->prepare($addressBooks);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $addressBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $counts = "SELECT COUNT(*) as total FROM address_book";
        $countStmt = $this->db->prepare($counts);
        $countStmt->execute();
        $counts = $countStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'addresses' => $addressBooks,
            'total' => $counts['total'],
            'limit' => $limit,
            'page' => $page
        ];
    }

    // Fetch a specific address by its ID
    public function getById($id)
    {
        $query = "SELECT * FROM address_book WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Fetch a specific address by its Name
    public function getByName($name, $id=null)
    {
        if(!empty($id)){
            $query = "SELECT * FROM address_book WHERE name = :name AND id <> :id";
        }else {
            $query = "SELECT * FROM address_book WHERE name = :name";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        if(!empty($id)) {
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Create a new address entry
    public function create($name, $email, $phone, $city_id, $street, $zipcode)
    {
        $query = "INSERT INTO address_book (name, email, phone, city_id, street, zipcode) VALUES (:name, :email, :phone, :city_id, :street, :zipcode)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, \PDO::PARAM_STR);
        $stmt->bindParam(':city_id', $city_id, \PDO::PARAM_INT);
        $stmt->bindParam(':street', $street, \PDO::PARAM_STR);
        $stmt->bindParam(':zipcode', $zipcode, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Update an existing address entry
    public function update($id, $name, $email, $phone, $city_id, $street, $zipcode)
    {
        $query = "UPDATE address_book SET name = :name, email = :email, phone = :phone, city_id = :city_id, street = :street, zipcode = :zipcode WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, \PDO::PARAM_STR);
        $stmt->bindParam(':city_id', $city_id, \PDO::PARAM_INT);
        $stmt->bindParam(':street', $street, \PDO::PARAM_STR);
        $stmt->bindParam(':zipcode', $zipcode, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Delete an address entry
    public function delete($id)
    {
        $query = "DELETE FROM address_book WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function export($format="json", $filename = "address_book") {
        $sql = "
            SELECT 
                address_book.id, 
                address_book.name, 
                address_book.email, 
                address_book.phone, 
                cities.name AS city_name,
                address_book.street, 
                address_book.zipcode 
            FROM address_book
            LEFT JOIN cities ON address_book.city_id = cities.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $addressBooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $format = $_GET['format'] ?? 'json';
        $exportData = Export::export($addressBooks, $format, $filename);

        if($filename) {
            file_put_contents($filename . '.' . $format, $exportData);
        }

        return $exportData;
    }
}
