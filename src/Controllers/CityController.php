<?php

namespace App\Controllers;

use App\Models\City;
use App\Helpers\View;
use App\Helpers\Notification;

class CityController
{
    private static $City;

    public function __construct()
    {
        // Instantiate the AddressBookModel
        self::$City = new City();
    }
    // Show all cities
    public function index()
    {
        // Get all cities from the model
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $q = $_GET['q'] ?? '';
        $data = self::$City->getAll($page, $limit, $q);

        // Render the view with cities data
        View::render('cities/index', 
        [
            'cities' => $data['cities'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit
        ]
    );
    }

    // Show form to create a new city
    public function create()
    {
        $error = '';
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'] ?? '';
            if(empty($name)) {
                $error = 'Name is required !!';
            } else {
                $city = self::$City->getByName($name);
                if ($city['name']) {
                    $error = 'Name already exists !!';
                }
            }

            if (empty($error)) {
                // Call the model to insert the new city
                self::$City->create($name);

                Notification::add('success', 'City: '. $name .' created successfully !!');
                // Redirect to the cities list after successful creation
                header('Location: /cities');
                exit;
            }
        }

        // Render the form to create a new city
        View::render('cities/create', ['error' => $error]);
    }

    // Show form to edit an existing city
    public function edit($id)
    {
        // Get city details from the model
        $city = self::$City->getById($id);
        $error = '';
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            if(empty($name)) {
                $error = 'Name is required !!';
            } else {
                $existCity = self::$City->getByName($name, $id);
                if ($existCity['name']) {
                    $error = 'Name already exists !!';
                }
            }

            if (!$error) {
                // Call the model to update the city
                self::$City->update($id, $name);

                Notification::add('success', 'City: '. $name .' updated successfully !!');
                // Redirect to the cities list after successful update
                header('Location: /cities');
                exit;
            }
        }

        if (!$city) {
            // If city doesn't exist, redirect to the cities list
            header('Location: /cities');
            exit;
        }

        // Render the form to edit the city
        View::render('cities/edit', ['city' => $city, 'error' => $error]);
    }

    // Handle city update
    public function update()
    {
        // Get data from the form
        $id = $_POST['id'];
        

        // If name is empty, render the edit form again with an error
        View::render('cities/edit', ['error' => 'City name is required.']);
    }

    // Handle city deletion
    public function delete($id)
    {
        $name = self::$City->getById($id)['name'];
        // Call the model to delete the city
        self::$City->delete($id);

        Notification::add('success', 'City: '. $name .' deleted successfully !!');
        // Redirect to the cities list after successful deletion
        header('Location: /cities');
        exit;
    }
}
