<?php
require "secrets.php";
require "inc/crypto.php";
require_once "inc/constants.php";

session_start();

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, "accounts");
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id_account = ?");
$stmt->bind_param("s", $_SESSION["auth_id"]);

$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_all(MYSQLI_ASSOC)[0];

if ($user["permissions"] && ACC_MANAGE_ACCOUNTS == 0) {
    header("Location: /error.php?errstring=Insufficient permissions");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $post_keys = array_keys($_POST);
    for ($i = 0; $i < count($post_keys); ++$i) {
	$current_key = $post_keys[$i];
	if ($current_key == "submit") {
	    break;
	} else {
	    $split = explode("_",$current_key);
	    $query = "UPDATE accounts SET ". $split[0] . "  = ? WHERE id_account = ?";
	    echo $query;
	    $stmt = $conn->prepare($query);
	    if (is_int($_POST[$current_key])) {
		$dtype = "ii";
	    } else {
		$dtype = "si";
	    }
	    $stmt->bind_param($dtype, $_POST[$current_key], $split[1]);
	    $stmt->execute();
	    
	}
    }
    $conn->close();
    header("Location: /mod.php?menu=manage");
    exit();
} else {
    header("Location: /mod.php");
    exit();
}

?>
