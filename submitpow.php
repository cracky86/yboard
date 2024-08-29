<?php
require "/srv/http/inc/crypto.php";
session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION["powSolution"] = $_POST["powSolution"];
}
header("Location: /pow.php");
exit();
?>
