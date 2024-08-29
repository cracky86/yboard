<?php
function getBoard() {
    // Get the current URL path
    $requestUri = $_SERVER['REQUEST_URI'];

    // Remove leading and trailing slashes
    $trimmedUri = trim($requestUri, '/');

    // Split the URI into parts
    $uriParts = explode('/', $trimmedUri);

    // Check if the URI matches the pattern
    if (isset($uriParts[0]) && $uriParts[0] == 'boards' && isset($uriParts[1])) {
	return $uriParts[1];	
    }
}

?>
