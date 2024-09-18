<?php
require_once("inc/constants.php");
require_once("inc/crypto.php");
session_start();

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, "accounts");
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id_account = ?");
$stmt->bind_param("s", $_SESSION["auth_id"]);

$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_all(MYSQLI_ASSOC)[0];

if ($user["permissions"] && ACC_CREATE_BOARD == 0) {
    header("Location: /error.php?errstring=Insufficient permissions");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $board_name = $_POST["boardname"];
    
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    $stmt = $conn->prepare("CREATE DATABASE " . $board_name);
    $stmt->execute();
    $conn->select_db($board_name);

    // Read the SQL file
    $sql = file_get_contents('imageboard/imageboard.sql');

    // Split the SQL file into individual queries
    $queries = explode(';', $sql);

    // Execute each query
    foreach ($queries as $query) {
	if (trim($query) != '') {
	    $conn->query($query);
	}
    }
    $conn->close();
    mkdir("boards/".$board_name, 0775);
    copy("imageboard/index.php", "boards/".$board_name."/index.php");
    
}
header("Location: /mod.php");
exit();
?>
