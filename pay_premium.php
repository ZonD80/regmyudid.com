<?php
$CONFIG['ENC_SECRET'] = 'sdfsdfsfw21';

require_once "auto_regger/database.class.php";
require_once 'init.php';

function validate_udid($udid) {
    return (preg_match('#^([a-z0-9+]{40})$#s', $udid) && !preg_match('/fffff/', $udid));
}

function encrypt($text, $salt) {
    if (!$text)
        return false;
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}

$udids = (array) $_POST['udids'];

if (count($udids) > 3) {
    die('Too much udids, dude');
}

$to_udids = array();
foreach ($udids as $udid) {
    $udid = (string) $udid;
    if ($udid) {
        if (!validate_udid($udid)) {
            die('<h1>One of UDIDs is invalid. <a href="https://regmyudid.com/#Section-5">How to find correct UDID?</a>');
        }
        $check = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='ok'");
        if (!$check) {
            die('<h1>One of UDIDs is not registered. Please register it as CERT to obtain premium package for it. <a href="javascript:history.go(-1);">Go back</a>');
        }
        $to_udids[] = $udid;
    }
}



$email = htmlspecialchars((string) $_POST['email']);

$encrypted_udids = encrypt(json_encode($to_udids), $CONFIG['ENC_SECRET']);

$src = "https://hosting.webtasti.co/r/premium.php?email=" . urlencode($email) . "&payment_data=" . urlencode($encrypted_udids);
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <title>RegMyUDID Payment</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
    </head>
    <body>
        <iframe src="<?php print $src; ?>" frameborder="0" allowtransparency="true" style="z-index: 9999; display: block; background: none repeat scroll 0% 0% transparent; border: 0px none transparent; overflow-x: hidden; overflow-y: auto; visibility: visible; margin: 0px; padding: 0px; position: fixed; left: 0px; top: 0px; width: 100%; height: 100%;"/>
    </body>
</html>