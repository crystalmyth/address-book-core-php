<?php

namespace App\Controllers;

use App\Models\AddressBook;
use App\Models\City;
use App\Helpers\View;
use App\Helpers\Notification;

class AddressBookController
{
    private static $AddressBook;
    private static $City;

    public function __construct()
    {
        // Instantiate the AddressBookModel
        self::$AddressBook = new AddressBook();
        self::$City = new City();
    }

    // Display the list of addresses
    public function index()
    {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        if(isset($_GET['q'])) {
            $data = self::$AddressBook->search($page, $limit, $_GET['q']);
        } else {
            $data = self::$AddressBook->getAll($page, $limit);
        }
        View::render('address-book/index', 
            [
                'addresses' => $data['addresses'],
                'total' => $data['total'],
                'page' => $page,
                'limit' => $limit
            ]
        );
    }

    // Display the form for creating a new address
    public function create()
    {
        $cities = self::$City->getAll();
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

            $address = self::$AddressBook->getByName($name);
            if ($address) {
                $errors['name'] = "Name already exists.";
            }
    
            if (!array_values($errors)[0]) {
                // Call the model to insert the new city
                self::$AddressBook->create($name, $email, $phone, $city_id, $street, $zipcode);

                Notification::add('success', 'Address: '. $name .' created successfully !!');
                // Redirect to the cities list after successful creation
                header('Location: /');
                exit;
            }
        }

        View::render('address-book/create', ['cities' => $cities['cities'], 'errors' => $errors]);
    }


    // Display the form for editing an address
    public function edit($id)
    {
        $cities = self::$City->getAll();
        $address = self::$AddressBook->getById($id);

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
            'zipcode' => null,
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get data from the form
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $city_id = $_POST['city_id'] ?? '';
            $street = $_POST['street'] ?? '';
            $zipcode = $_POST['zipcode'] ?? '';

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

            $existAddress = self::$AddressBook->getByName($name, $id);
            if ($existAddress) {
                $errors['name'] = "Name already exists.";
            }
            
            if (!array_values($errors)[0]) {
                // Call the model to insert the new city
                self::$AddressBook->update($id, $name, $email, $phone, $city_id, $street, $zipcode);

                Notification::add('success', 'Address: '. $name .' updated successfully !!');
                // Redirect to the cities list after successful creation
                header('Location: /');
                exit;
            }
        }

        View::render('address-book/edit', ['address' => $address, 'cities' => $cities['cities'], 'errors' => $errors]);
    }


    // Export address book data to a CSV file
    public function export()
    {
        $format = $_GET['format'] ?? 'csv';
        $filename = $_GET['filename'] ?? 'address_book';
        $data = self::$AddressBook->getAll();
        
        self::$AddressBook->export($format, $filename);
        exit;
    }

    // Handle address book deletion
    public function delete($id)
    {
        $name = self::$AddressBook->getById($id)['name'];
        // Call the model to delete the city
        self::$AddressBook->delete($id);

        Notification::add('success', 'Address: '. $name .' deleted successfully !!');
        // Redirect to the cities list after successful deletion
        header('Location: /');
        exit;
    }
}
