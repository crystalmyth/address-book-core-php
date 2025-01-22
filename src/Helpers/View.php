<?php

namespace App\Helpers;

class View
{
    // Renders the view file with the given data
    public static function render($view, $data = [])
    {
        // Extract the data array to variables
        extract($data);

        // Capture the output of the view
        ob_start();
        $viewPath = str_replace('.', __DIR__ . '/../Views/', $view);
        include __DIR__ . '/../Views/' . $viewPath . '.phtml';
        $content = ob_get_clean();

        // Now render the layout and pass the content to it
        include __DIR__ . '/../Views/layout.phtml';
    }
}
