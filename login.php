<?php
require "secrets.php";
require "inc/crypto.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
$username = $_POST["username"];
$password = $_POST["pass"];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, "accounts");
$stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
$stmt->bind_param("s", $username);

$stmt->execute();
$stmt->bind_result($id, $acc_username, $password_hash, $perms, $last_datetime, $password_attempts);

if ($stmt->fetch()) {
// Verify the password
if (password_verify($password, $password_hash)) {
$stmt->close();

// Update last login time
$stmt = $conn->prepare("UPDATE accounts SET last_login = ? WHERE id_account = ?");
$datetime = date('Y-m-d H:i:s');
$stmt->bind_param("si", $datetime, $id);
$stmt->execute(); // Execute the statement
$stmt->close();

// Successful login
session_start();
$_SESSION["auth_id"] = $id;
header("Location: /mod.php");
exit();
} else {
$stmt->close();

// Update wrong password attempts
$stmt = $conn->prepare("UPDATE accounts SET wrong_password_attempts = ? WHERE id_account = ?");
$new_attempts = ++$password_attempts;  // Increment attempts
$stmt->bind_param("ii", $new_attempts, $id);
$stmt->execute(); // Execute the statement
$stmt->close();

// Incorrect password
header("Location: /error.php?errstring=Wrong password");
exit();
}
} else {
// User not found
header("Location: /error.php?errstring=User not found");
exit();
}

$stmt->close();
$conn->close();
} else {
header("Location: /mod.php");
}
?>
