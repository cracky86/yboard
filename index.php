<?php
require_once "/srv/http/setup.php";

$path = "/srv/http/boards/";
$boards = array_diff(scandir($path), array('.', '..'));

echo $twig->render("frontpage.html.twig", ["boards" => $boards]);

?>
