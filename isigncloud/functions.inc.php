<?php

define('ROOT_PATH', dirname(__FILE__) . '/');
define('TICKET_SS', 'FJH23OIHGOUEHG');
define('ONE_APP_PER_TIME', false);
define('SITE_ADDRESS','https://regmyudid.com/isigncloud');
ob_start('ob_gzhandler');

//die(ROOT_PATH);
/*
enum SWAGSignErrorName {
    SWAGSIGNSuccess = 0,
    SWAGSIGNErrorUnzipFailed = -100,
    SWAGSIGNErrorAppNotFound = -101,
    SWAGSIGNErrorAWDRNotFound = -102,
    SWAGSIGNErrorCertNotFound = -103,
    SWAGSIGNErrorUserIntercationNotAllowed = -104,
    SWAGSIGNErrorSignFailed = -199,
    SWAGSIGNErrorInfoNotFound = -200,
    SWAGSIGNErrorNotMachOExec = -201
};
 */

function str_clean($string) {
    $string = str_replace('', '-', $string); // Replaces all spaces with hyphens.
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function mksize($bytes)
{
    if ($bytes < 1000 * 1024)
        return number_format($bytes / 1024, 2) . " kB";
    elseif ($bytes < 1000 * 1048576)
        return number_format($bytes / 1048576, 2) . " MB";
    elseif ($bytes < 1000 * 1073741824)
        return number_format($bytes / 1073741824, 2) . " GB";
    else
        return number_format($bytes / 1099511627776, 2) . " TB";
}

function loggy($string)
{
     /*global $_LOG_HANDLE;

       if (!$_LOG_HANDLE)
       $_LOG_HANDLE = fopen(LOG_FILE, 'a');

       fwrite($_LOG_HANDLE, "$string\n");*/
}

function result($data)
{
    die(json_encode($data));
}

function encrypt($text, $salt)
{
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}

function decrypt($text, $salt)
{
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND)));
}
?>