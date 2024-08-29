<?php
require_once 'vendor/autoload.php';

// Specify where your templates are stored
$loader = new \Twig\Loader\FilesystemLoader('/srv/http/templates/');

// Initialize the Twig environment
$twig = new \Twig\Environment($loader, [
    'debug' => true, // Enable for debugging
]);
