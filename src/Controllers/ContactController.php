<?php

namespace App\Controllers;

use App\Models\Contact;
use App\Models\City;
use App\Models\Group;
use App\Helpers\View;
use App\Helpers\Notification;

class ContactController
{
    private static $Contact;
    private static $City;
    private static $Group;

    public function __construct()
    {
        // Instantiate the ContactModel
        self::$Contact = new Contact();
        self::$City = new City();
        self::$Group = new Group();
    }

    // Display the list of addresses
    public function index()
    {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $q = $_GET['q'] ?? '';
        $tag = $_GET['tag'] ?? '';
        $data = self::$Contact->getAll($page, $limit, $q, $tag);
        View::render('address-book/index', 
            [
                'addresses' => $data['addresses'],
                'total' => $data['total'],
                'tags' => $data['tags'],
                'page' => $page,
                'limit' => $limit
            ]
        );
    }

    // Display the form for creating a new address
    public function create()
    {
        $cities = self::$City->getAll();
        $groups = self::$Group->getAll()['groups'];
        $errors = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'city_id' => '',
            'street' => '',
            'zipcode' => '',
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get data from the form
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $city_id = $_POST['city_id'] ?? '';
            $street = $_POST['street'] ?? '';
            $zipcode = $_POST['zipcode'] ?? '';
            $tags = $_POST['tags'] ?? [];
            $groups = array_unique($_POST['groups']) ?? [];

            if(empty($name)) {
                $errors['name'] = "Name is required.";
            }
            if(empty($phone)) {
                $errors['phone'] = "Phone is required.";
            }
            if(empty($email)) {
                $errors['email'] = "Email is required.";
            }
            if(empty($city_id)) {
                $errors['city_id'] = "City is required.";
            }
            if(empty($street)) {
                $errors['street'] = "Street is required.";
            }
            if(empty($zipcode)) {
                $errors['zipcode'] = "Zipcode is required.";
            }

            $address = self::$Contact->getByName($name);
            if ($address) {
                $errors['name'] = "Name already exists.";
            }
    
            if (!array_values($errors)[0]) {
                // Call the model to insert the new city
                self::$Contact->create($name, $email, $phone, $city_id, $street, $zipcode, $tags, $groups);

                Notification::add('success', 'Address: '. $name .' created successfully !!');
                // Redirect to the cities list after successful creation
                header('Location: /');
                exit;
            }
        }

        View::render('address-book/create', ['cities' => $cities['cities'], 'groups' => $groups, 'errors' => $errors]);
    }


    // Display the form for editing an address
    public function edit($id)
    {
        $cities = self::$City->getAll();
        $address = self::$Contact->getById($id);
        $groups = self::$Group->getAll()['groups'];

        if (!$address) {
            echo "<h1>Address Not Found</h1>";
            exit;
        }

        $errors = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'city_id' => null,
            'street' => null,
            'zipcode' => null
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get data from the form
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $city_id = $_POST['city_id'] ?? '';
            $street = $_POST['street'] ?? '';
            $zipcode = $_POST['zipcode'] ?? '';
            $tags = $_POST['tags'] ?? '';
            $groups = $_POST['groups'] ?? '';

            if(empty($name)) {
                $errors['name'] = "Name is required.";
            }
            if(empty($phone)) {
                $errors['phone'] = "Phone is required.";
            }
            if(empty($email)) {
                $errors['email'] = "Email is required.";
            }
            if(empty($city_id)) {
                $errors['city_id'] = "City is required.";
            }
            if(empty($street)) {
                $errors['street'] = "Street is required.";
            }
            if(empty($zipcode)) {
                $errors['zipcode'] = "Zipcode is required.";
            }

            $existAddress = self::$Contact->getByName($name, $id);
            if ($existAddress) {
                $errors['name'] = "Name already exists.";
            }
            
            if (!array_values($errors)[0]) {
                // Call the model to insert the new city
                self::$Contact->update($id, $name, $email, $phone, $city_id, $street, $zipcode, $tags, $groups);
                Notification::add('success', 'Address: '. $name .' updated successfully !!');
                // Redirect to the cities list after successful creation
                header('Location: /');
                exit;
            }
        }

        View::render('address-book/edit', ['address' => $address, 'cities' => $cities['cities'], 'groups' => $groups, 'errors' => $errors]);
    }


    // Export address book data to a CSV file
    public function export()
    {
        $format = $_GET['format'] ?? 'csv';
        $filename = $_GET['filename'] ?? 'address_book';
        $data = self::$Contact->getAll();
        
        self::$Contact->export($format, $filename);
        exit;
    }

    // Handle address book deletion
    public function delete($id)
    {
        $name = self::$Contact->getById($id)['name'];
        // Call the model to delete the city
        self::$Contact->delete($id);

        Notification::add('success', 'Address: '. $name .' deleted successfully !!');
        // Redirect to the cities list after successful deletion
        header('Location: /');
        exit;
    }
}
