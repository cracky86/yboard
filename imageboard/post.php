<?php
require "/srv/http/secrets.php";
require "/srv/http/inc/validate-post.php";
require "/srv/http/inc/crypto.php";

session_start();

if (validatePoW($_SESSION["powSolution"]) == false) {
    header("Location: /pow.php");
    exit();    
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $name = $_POST["name"];
        $subject = $_POST["subject"];
        $message = $_POST["msg"];
        $captcha = $_POST["captcha"];
        $postpassword = $_POST["postpassword"];

        if (isset($postpassword)) {
            $postpassword = bcrypt($postpassword);
        } else {
            $postpassword = "";
        }

        if ($name == ""){
            $name = "Anonymous";
        }

        validatePost($name, $captcha, $message);

        $name = generateTripcode($name);

        [$uploadOk,$filepath] = handleUpload($_FILES["file"]);

        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

        if ($conn->connect_error) {
            header("Location: /error.php?errstring=Database connection failed");
            exit();
        }
        
        // Query to count rows in the replies table
        $sql = "SELECT COUNT(*) AS count FROM replies";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $replies_count = $row['count'];

        // Query to count rows in the posts table
        $sql = "SELECT COUNT(*) AS count FROM posts";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $posts_count = $row['count'];

        // Make every post or reply have an unique ID
        $post_id = $posts_count + $replies_count + 1;
        
        $stmt = $conn->prepare(
            "INSERT INTO posts (post_id, post_title, poster_name, post_content, has_image, post_image_path, postpassword, post_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $timestamp = time();
        $stmt->bind_param(
            "isssissi",
            $post_id,
            $subject,
            $name,
            $message,
            $uploadOk,
            $filepath,
            $postpassword,
            $timestamp
        );

        if ($stmt->execute()) {
            header("Location: index.php");
        } else {
            header("Location: /error.php?errstring=Failed to post message");
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $exc) {
        header("Location: /error.php?errstring=" . urlencode($exc->getMessage()));
    }
} else {
    header("Location: /error.php?errstring=You haven't posted anything");
    exit();
}
?>
