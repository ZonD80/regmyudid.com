<?php

$data = file_get_contents('php://input');
preg_match('#([a-z0-9+]{40})#s', $data, $matches);

$udid = $matches[1];


header('HTTP/1.1 301 Moved Permatently');

header("Location: https://regmyudid.com/get_udid/?udid={$udid}");
?>