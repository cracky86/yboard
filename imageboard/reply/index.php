<?php
require '/srv/http/inc/crypto.php';
require_once '/srv/http/setup.php'; // Assuming this is where Twig is initialized
require_once '/srv/http/inc/db-utils.php';

session_start();

if (isset($_GET['thread'])) {
    $thread_id = intval($_GET['thread']);
    $_SESSION['thread_id'] = $thread_id;
} else {
    header('Location: ..');
}

if (!isset($_SESSION["powSolution"])) {
    header("Location: /pow.php");
    exit();
}
if (validatePoW($_SESSION["powSolution"]) == false) {
    header("Location: /pow.php");
    exit();    
}

// Initialize Twig
//$loader = new \Twig\Loader\FilesystemLoader('/path/to/templates');
//$twig = new \Twig\Environment($loader);

// Create connection
$database = getBoard();
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch thread post and replies
$stmt = $conn->prepare("SELECT * FROM posts WHERE post_id = ?");
$stmt->bind_param('i', $thread_id);
$stmt->execute();
$post_result = $stmt->get_result();
$stmt->close();

$post = $post_result->fetch_assoc();

$stmt_replies = $conn->prepare("SELECT * FROM replies WHERE post_id_fk = ?");
$stmt_replies->bind_param('i', $thread_id);
$stmt_replies->execute();
$replies_result = $stmt_replies->get_result();
$stmt_replies->close();

// Fetch replies
$replies = [];
while ($reply = $replies_result->fetch_assoc()) {
    $replies[] = $reply;
}

$conn->close();

// Render the template
echo $twig->render('replies.html.twig', [
    'post' => $post,
    'replies' => $replies,
    'thread_id' => $thread_id
]);
