<?php
session_start();
header("Content-Type: image/png");
$img = base64_decode($_SESSION["captcha_section"]);

echo $img;
?>
