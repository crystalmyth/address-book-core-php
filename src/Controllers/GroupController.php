<?php

namespace App\Controllers;

use App\Models\Group;
use App\Helpers\View;
use App\Helpers\Notification;

class GroupController
{
    private static $Group;

    public function __construct()
    {
        // Instantiate the ContactModel
        self::$Group = new Group();
    }
    // Show all groups
    public function index()
    {
        // Get all groups from the model
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $q = $_GET['q'] ?? '';
        $data = self::$Group->getAll($page, $limit, $q);

        // Render the view with groups data
        View::render('groups/index', 
        [
            'groups' => $data['groups'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit
        ]
    );
    }

    // Show form to create a new group
    public function create()
    {
        $groups = self::$Group->getAll()['groups'];
        $error = '';
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'] ?? '';
            $parent_ids = $_POST['parent_ids'] ?? [];
            if(empty($name)) {
                $error = 'Name is required !!';
            } else {
                $group = self::$Group->getByName($name);
                if ($group) {
                    $error = 'Name already exists !!';
                }
            }

            if (empty($error)) {
                // Call the model to insert the new group
                self::$Group->create($name, $parent_ids);

                Notification::add('success', 'Group: '. $name .' created successfully !!');
                // Redirect to the groups list after successful creation
                header('Location: /groups');
                exit;
            }
        }

        // Render the form to create a new group
        View::render('groups/create', ['groups' => $groups, 'error' => $error]);
    }

    // Show form to edit an existing group
    public function edit($id)
    {
        // Get group details from the model
        $groups = self::$Group->getAll()['groups'];
        $groups = array_filter($groups, function ($group) use ($id) {
            return $group['id'] != $id;
        });
        $group = self::$Group->getById($id);
        $contacts = self::$Group->get_contacts_by_group_id($id);
        $error = '';

        $parent_ids =  [];
        if($group['parent_ids']) {
            $parent_ids = explode(",", $group['parent_ids']);
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $parent_ids = $_POST['parent_ids'] ?? [];

            if(empty($name)) {
                $error = 'Name is required !!';
            } else {
                $existGroup = self::$Group->getByName($name, $id);
                if ($existGroup['name']) {
                    $error = 'Name already exists !!';
                }
            }

            if (!$error) {
                // Call the model to update the group
                self::$Group->update($id, $name, $parent_ids);

                Notification::add('success', 'Group: '. $name .' updated successfully !!');
                // Redirect to the groups list after successful update
                header('Location: /groups');
                exit;
            }
        }

        if (!$group) {
            // If group doesn't exist, redirect to the groups list
            header('Location: /groups');
            exit;
        }

        // Render the form to edit the group
        View::render('groups/edit', [
            'parent_ids' => $parent_ids,
            'groups' => $groups,
            'group' => $group,
            'contacts' => $contacts,
            'error' => $error
        ]);
    }

    // Handle group deletion
    public function delete($id)
    {
        $name = self::$Group->getById($id)['name'];
        // Call the model to delete the group
        self::$Group->delete($id);

        Notification::add('success', 'Group: '. $name .' deleted successfully !!');
        // Redirect to the groups list after successful deletion
        header('Location: /groups');
        exit;
    }
}
