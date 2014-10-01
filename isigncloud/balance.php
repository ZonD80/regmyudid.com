<?php

$udid = (string) $_GET['udid'];

header('Content-type: application/json; charset=utf-8');
require_once "../auto_regger/database.class.php";
require_once '../init.php';

$premium = $DB->query_row("SELECT * FROM premium_purchases WHERE udid={$DB->sqlesc($udid)}");

if ($premium) {
    $sign_server = 'https://sspremium.regmyudid.com';
} else {
    $sign_server = 'https://ss1.regmyudid.com';
}

die(($sign_server));