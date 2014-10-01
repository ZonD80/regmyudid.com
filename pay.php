<?php
$CONFIG['ENC_SECRET'] = 'sdfsdfsfw21';

function validate_udid($udid) {
    return (preg_match('#^([a-z0-9+]{40})$#s', $udid) && !preg_match('/fffff/', $udid));
}

function encrypt($text, $salt) {
    if (!$text)
        return false;
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}

$udid = (string) $_POST['udid'];

if (!validate_udid($udid)) {
    die('<h1>This UDID is invalid. <a href="https://regmyudid.com/#Section-5">How to find correct UDID?</a>');
}

$type = (string) $_POST['type'];

if (!in_array($type, array('REG', 'CERT'))) {
    die('Hacking attempt, invalid type');
}

$udidsend = ($udid);
if ($type == 'REG') {
    $src = "http://rmupayments.com/pay/standard.php?udidsend=" . urlencode($udidsend);
} elseif ($type == 'CERT') {
    $src = "http://rmupayments.com/pay/advanced.php?udidsend=" . urlencode($udidsend);
}
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
        <?php
/* Redirect browser */
header("Location: $src");
 
/* Make sure that code below does not get executed when we redirect. */
exit;
?>
    </body>
</html>