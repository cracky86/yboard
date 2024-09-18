<?php
require_once "setup.php";
require_once "secrets.php";
require_once "inc/crypto.php";
require_once "inc/constants.php";

session_start();

// if not authed set their authid to 0 (not logged in)
if (! isset($_SESSION["auth_id"])) {
    $_SESSION["auth_id"] = 0;
}

// connect to the database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, "accounts");

// retreive user info
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id_account = ?");
$stmt->bind_param("i", $_SESSION["auth_id"]);
$stmt->execute();
$stmt->bind_result($id, $username, $password, $perms,$last_logon,$wrong_password_attempts);
$stmt->fetch();

$stmt->close();

// Prepare the SQL statement
$stmt = $conn->prepare("SELECT * FROM accounts");

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch all the results into an associative array
$users = $result->fetch_all(MYSQLI_ASSOC);

// Close the statement
$stmt->close();

// print_r($users);

if ($perms & ACC_DISABLED) {
    header("Location: /error.php?errstring=Your account is disabled, please contact the administrator for details");
    exit();
}

// create array of permission values used for checking permissions within the twig template
$perms_array = array();

for ($i = 0; $i <= 32; $i++) {
    // shift 1 to the left by $i bits, then check if the bit is set
    $perms_array[] = ($perms & (1 << $i)) != 0;
}

if (! isset($_GET["menu"])) {
    $menu = "";
} else {
    $menu = $_GET["menu"];
}

echo $twig->render("mod.html.twig", ["auth" => $_SESSION["auth_id"], "username" => $username, "perms" => $perms_array, "menu" => $menu, "users" => $users,"last_logon" => $last_logon, "logon_attempts" => $wrong_password_attempts]);


?>
