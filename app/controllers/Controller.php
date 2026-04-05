<?php

class Controller {

    // Load a view file
    protected function view($view, $data = []) {
        // Make $data keys available as variables inside the view
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die('View not found: ' . $view);
        }
    }

    // Load a model
    protected function model($model) {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model();
    }
}