<?php

require_once 'functions.inc.php';
$data = file_get_contents('php://input');
preg_match('#([a-z0-9+]{40})#s', $data, $matches);

$udid = $matches[1];

require_once "../auto_regger/database.class.php";
require_once '../init.php';

$premium = $DB->query_row("SELECT * FROM premium_purchases WHERE udid={$DB->sqlesc($udid)}");

if ($premium) {
    $sign_server = 'https://sspremium.regmyudid.com';
} else {
    $sign_server = 'https://ss1.regmyudid.com';
}
//$sign_server = 'https://sspremium.regmyudid.com';
header('HTTP/1.1 301 Moved Permatently');
if (!$_REQUEST['udid_only']) {

    //file_put_contents('test.txt', var_export($_REQUEST,true));
    $encoded_ticket = htmlspecialchars((string) $_REQUEST['t']);

    header("Location: $sign_server/d/?udid={$udid}&t=" . urlencode($encoded_ticket));
} else {
    header("Location: appaddict://udid=$udid");
}
?>