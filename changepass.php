<?php
require "secrets.php";
require "inc/crypto.php";
require "inc/constants.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["pass"];

    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, "accounts");
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id_account = ?");
    $stmt->bind_param("i", $_SESSION["auth_id"]);

    $stmt->execute();
    $stmt->bind_result($id,$acc_username,$password_hash,$perms,$last_logon,$wrong_pass_attempts);
    $stmt->fetch();
    $stmt->close();

    // change password and password change flag
    $stmt = $conn->prepare("UPDATE accounts SET password = ?, permissions = ? WHERE id_account = ?");
    $hash=bcrypt($password);
    $newperms=$perms-2;
    
    $stmt->bind_param("sii", $hash,$newperms,$_SESSION["auth_id"]);

    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    header("Location: /mod.php");
} else {
    header("Location: /mod.php");
}

?>
