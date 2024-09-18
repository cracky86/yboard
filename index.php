<?php
require_once "setup.php";

$path = "boards/";
$boards = array_diff(scandir($path), array('.', '..'));

echo $twig->render("frontpage.html.twig", ["boards" => $boards]);

?>
