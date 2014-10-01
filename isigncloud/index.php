<?php

require_once 'functions.inc.php';

$name = htmlspecialchars((string) $_GET['name']);
$link = urldecode((string) $_GET['link']);
$image = urldecode((string) $_GET['image']);
$bundle_id = htmlspecialchars((string) $_GET['bundle_id']);
$force_sign = ($_GET['force_sign'] ? true : false);
$email = htmlspecialchars((string) $_GET['email']);
$ticket = array('name' => $name, 'link' => $link, 'image' => $image, 'bundle_id' => $bundle_id, 'force_sign' => $force_sign);
//var_dump($ticket);
$ticket = encrypt(json_encode($ticket), 'RMUSS_super_secret');

die($ticket);
