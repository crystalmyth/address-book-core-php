<?php

namespace App\Helpers;

class Notification
{
    public static function add($type, $message)
    {
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }

        $_SESSION['notifications'][] = ['type' => $type, 'message' => $message];
    }

    public static function get()
    {
        $notifications = $_SESSION['notifications'] ?? [];
        unset($_SESSION['notifications']); // Clear notifications after retrieval
        return $notifications;
    }
}
