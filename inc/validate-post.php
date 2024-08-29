<?php
// Function to handle redirection to error page
function redirectToError($message) {
    header("Location: /error.php?errstring=" . urlencode($message));
    exit();
}

function validatePost($name, $captcha, $message) {
    session_start();
    if (strpos($name, "!") == true) {
        header("Location: /error.php?errstring=Illegal character in name");
        exit();
    }
    if ($message=="") {
        header("Location: /error.php?errstring=Message cannot be empty");
        exit();
    }
    if ($captcha != $_SESSION["captcha_text"] or $_SESSION["captcha_text"] == "") {
        header("Location: /error.php?errstring=Invalid CAPTCHA");
        exit();
    }
    $_SESSION["captcha_text"] = "";
}
function handleUpload($uploadedFile) {
    $uploadOk = 1;
    $target_dir = "/srv/http/images/";
    $filename = basename($uploadedFile["name"]);

    // Sanitize filename to prevent path traversal attacks
    $filename = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $filename);

    // Prevent path traversal by rejecting filenames with '/../' and '~'
    if ($uploadedFile["name"]=="") {
        return array(false, null);
    }
    if (strpos($filename, '../') !== false || strpos($filename, '~') !== false) {
        error_log("Invalid filename: $filename");
        redirectToError("Invalid filename.");
    }
    
    $target_file = $target_dir . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    // Check file type
    if (!in_array($imageFileType, $allowedExtensions)) {
        error_log("Invalid file type: $imageFileType");
        redirectToError("Invalid file type");
    }

    // Check file size (limit: 2MB)
    if ($uploadedFile["size"] > 2097152) {
        error_log("File is too large: " . $uploadedFile["size"]);
        redirectToError("File is too large");
    }

    // Check if file was uploaded without errors
    if ($uploadedFile["error"] != UPLOAD_ERR_OK) {
        error_log("File upload error: " . $uploadedFile["error"]);
        redirectToError("File upload error");
    }

    // Validate image file
    if (getimagesize($uploadedFile["tmp_name"]) === false) {
        error_log("File is not a valid image.");
        redirectToError("File is not a valid image");
    }

    // Generate a unique filename to prevent overwriting and ensure uniqueness
    $unique_filename = uniqid() . "_" . $filename;
    $target_file = $target_dir . $unique_filename;

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile["tmp_name"], $target_file)) {
        error_log("Failed to move uploaded file.");
        redirectToError("Failed to move uploaded file.");
    }

    // Create a thumbnail image
    $thumb_target_file = $target_dir . "THUMB_" . $unique_filename;
    createThumbnail($target_file, $thumb_target_file, 250);

    $target_file = "/images/" . $unique_filename;
    
    return array($uploadOk, $target_file);
}

function createThumbnail($source_file, $destination_file, $thumb_height) {
    $image_info = getimagesize($source_file);
    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];
    
    // Calculate thumbnail width to preserve aspect ratio
    $thumb_width = intval(($thumb_height / $height) * $width);
    
    switch ($mime) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_file);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_file);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_file);
            break;
        default:
            return false;
    }

    // Create a blank image for the thumbnail
    $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);

    // Resize the original image into the thumbnail
    imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

    // Save the thumbnail image
    if ($height >= $thumb_height) {
        $img = $thumb_image;
    } else {
        $img = $source_image;
    }
    switch ($mime) {
    case 'image/jpeg':
        imagejpeg($img, $destination_file);
        break;
    case 'image/png':
        imagepng($img, $destination_file);
        break;
    case 'image/gif':
        imagegif($img, $destination_file);
        break;
    }
    // Free up memory
    imagedestroy($source_image);
    imagedestroy($thumb_image);
}

?>
