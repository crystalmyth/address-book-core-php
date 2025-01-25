<?php

namespace App\Models;

use App\Helpers\Database;
use App\Helpers\Export;
use PDO;
use PDOException;
class Contact
{
    private $db;

    public function __construct()
    {
        // Initialize the database connection
        $this->db = Database::getConnection();
    }

    // Fetch all addresses from the database
    public function getAll($page = 1, $limit = 10, $q = '', $tag = '')
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT 
                a.*, 
                c.name AS city_name
            FROM 
                contacts a
            LEFT JOIN 
                cities c ON a.city_id = c.id
            LEFT JOIN 
                contact_tags ct ON a.id = ct.contact_id
            WHERE 
                (
                    (:q IS NOT NULL AND (
                        a.name LIKE :q 
                        OR a.email LIKE :q 
                        OR a.phone LIKE :q 
                        OR c.name LIKE :q
                    )) 
        ";
        
        if(!empty($tag)) {
            $sql .= "
                AND (:tag IS NOT NULL AND ct.name = :tag)
            ";
        }

        $sql .=  "
                )
            GROUP BY 
                a.id
            ORDER BY 
                a.id DESC
            LIMIT :offset, :limit;

        ";
        
        $stmt = $this->db->prepare($sql);
        $searchTerm = '%' . $q . '%';
        $stmt->bindParam(':q', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':tag', $tag, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = "SELECT COUNT(*) as total FROM contacts";
        $countStmt = $this->db->prepare($counts);
        $countStmt->execute();
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

        $tagsStmt = $this->db->prepare("SELECT DISTINCT name FROM contact_tags");
        $tagsStmt->execute();
        $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'addresses' => $contacts,
            'total' => $counts['total'],
            'tags' => $tags,
            'limit' => $limit,
            'page' => $page
        ];
    }

    // Fetch a specific address by its ID
    public function getById($id)
    {
        $query = "
            SELECT 
                c.*, 
                GROUP_CONCAT(t.name SEPARATOR ',') as tags, 
                GROUP_CONCAT(gc.group_id SEPARATOR ',') as group_ids
            FROM 
                contacts c
            LEFT JOIN 
                contact_tags t ON c.id = t.contact_id
            LEFT JOIN 
                group_contacts gc ON c.id = gc.contact_id AND gc.inherited = 0
            WHERE 
                c.id = :id
            GROUP BY 
                c.id;
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch a specific address by its Name
    public function getByName($name, $id=null)
    {
        if(!empty($id)){
            $query = "SELECT * FROM contacts WHERE name = :name AND id <> :id";
        }else {
            $query = "SELECT * FROM contacts WHERE name = :name";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        if(!empty($id)) {
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create a new address entry
    public function create($name, $email, $phone, $city_id, $street, $zipcode, $tags, $groups)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO contacts (name, email, phone, city_id, street, zipcode) VALUES (:name, :email, :phone, :city_id, :street, :zipcode)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':city_id', $city_id, PDO::PARAM_INT);
            $stmt->bindParam(':street', $street, PDO::PARAM_STR);
            $stmt->bindParam(':zipcode', $zipcode, PDO::PARAM_STR);
            $stmt->execute();

            $contact_id = $this->db->lastInsertId();
            if(!empty($tags)) {
                $this->createContactTags($tags, $contact_id);
            }
            
            foreach($groups as $group) {
                $this->createGroupContacts($contact_id, $group);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "Error applying create contact: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    // Update an existing address entry
    public function update($id, $name, $email, $phone, $city_id, $street, $zipcode, $tags, $groups)
    {
        try {
            $query = "UPDATE contacts SET name = :name, email = :email, phone = :phone, city_id = :city_id, street = :street, zipcode = :zipcode WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':city_id', $city_id, PDO::PARAM_INT);
            $stmt->bindParam(':street', $street, PDO::PARAM_STR);
            $stmt->bindParam(':zipcode', $zipcode, PDO::PARAM_STR);
            $stmt->execute();

            // delete all connected tags
            $this->deleteAllContactTags($id);

            // create new tag connections
            if(!empty($tags)) {
                $this->createContactTags($tags, $id);
            }

            // delete all connected groups
            $this->deleteAllContactGroups($id);

            // create new group connections
            foreach($groups as $group) {
                $this->createGroupContacts($id, $group);
            }

            $this->db->commit();
            return true;

        }catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "Error applying update contact: " . $e->getMessage() . "<br>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    // Delete an address entry
    public function delete($id)
    {
        $this->deleteAllContactTags($id);
        $this->deleteAllContactGroups($id);
        $query = "DELETE FROM contacts WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    public function export($format="json", $filename = "contacts") {
        $sql = "
            SELECT 
                a.id, 
                a.name, 
                a.email, 
                a.phone, 
                c.name AS city_name,
                a.street, 
                a.zipcode,
                GROUP_CONCAT(ct.name SEPARATOR ', ') AS tags 
            FROM contacts a
            LEFT JOIN cities c ON a.city_id = c.id
            LEFT JOIN contact_tags ct ON a.id = ct.contact_id
            GROUP BY a.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $format = $_GET['format'] ?? 'json';
        $exportData = Export::export($contacts, $format, $filename);

        if($filename) {
            file_put_contents($filename . '.' . $format, $exportData);
        }

        return $exportData;
    }


    private function createContactTags($tags, $contact_id)
    {
        if(!empty($tags)) {
            foreach(json_decode($tags) as $tag) {
                $tagStmt = $this->db->prepare("INSERT INTO contact_tags (name, contact_id) VALUES (:name, :contact_id)");
                $tagStmt->bindParam(":name", $tag, PDO::PARAM_STR);
                $tagStmt->bindParam(":contact_id", $contact_id, PDO::PARAM_INT);
                $tagStmt->execute();
            }
        }
        return true;
    }

    private function deleteAllContactTags($contact_id) {
        // Delete existing tags for the contact
        $deleteTagStmt = $this->db->prepare("DELETE FROM contact_tags WHERE contact_id = :contact_id");
        $deleteTagStmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
        return $deleteTagStmt->execute();
    }

    private function createGroupContacts($contact_id, $group_id, $inherited = FALSE) 
    {
        $parent_id = $this->getParentId($group_id);
        if($parent_id) {
            $this->createGroupContacts($contact_id, $parent_id, TRUE);
        }
        
        $sql = "INSERT INTO group_contacts (group_id, contact_id, inherited) VALUES (:group_id, :contact_id, :inherited)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':group_id', $group_id, \PDO::PARAM_INT);
        $stmt->bindParam(':contact_id', $contact_id, \PDO::PARAM_INT);
        $stmt->bindParam(':inherited', $inherited, \PDO::PARAM_BOOL);
        $stmt->execute();

        return true;
    }

    private function deleteAllContactGroups($contact_id) {
        // Delete existing groups for the contact
        $deleteTagStmt = $this->db->prepare("DELETE FROM group_contacts WHERE contact_id = :contact_id");
        $deleteTagStmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
        return $deleteTagStmt->execute();
    }

    public function getParentId($group_id) {
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
        $sql = $this->db->prepare($query);
        $sql->bindParam(':group_id', $group_id, \PDO::PARAM_INT);
        $sql->execute();
        $result = $sql->fetch(\PDO::FETCH_ASSOC);
        return $result['parent_id'];
    }
}
