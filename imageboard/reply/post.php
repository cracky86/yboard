<?php
require "/srv/http/secrets.php";
require "/srv/http/inc/validate-post.php";
require "/srv/http/inc/crypto.php";

session_start();

if (!isset($_SESSION["powSolution"])) {
    header("Location: /pow.php");
    exit();
}
if (validatePoW($_SESSION["powSolution"]) == false) {
    header("Location: /pow.php");
    exit();    
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $name = isset($_POST["name"]) ? trim($_POST["name"]) : "Anonymous";
        $message = isset($_POST["msg"]) ? trim($_POST["msg"]) : "";
        $captcha = isset($_POST["captcha"]) ? trim($_POST["captcha"]) : "";
        $postpassword = isset($_POST["postpassword"]) ? trim($_POST["postpassword"]) : "";

        if ($name == "") {
            $name = "Anonymous";
        }

        validatePost($name, $captcha, $message);

        $name = generateTripcode($name);

        [$uploadOk,$filepath] = handleUpload($_FILES["file"]);

        // Database connection
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
        
        $post_id_fk = $_SESSION["thread_id"];
        // Prepare and bind SQL statement
        $stmt = $conn->prepare(
            "INSERT INTO replies (reply_id, replier_name, reply_content, has_image, reply_image_path, reply_password, reply_timestamp, post_id_fk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $timestamp = time();
        $stmt->bind_param(
            "ississii",
            $post_id,
            $name,
            $message,
            $uploadOk,
            $filepath,
            $postpassword,
            $timestamp,
            $post_id_fk
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
