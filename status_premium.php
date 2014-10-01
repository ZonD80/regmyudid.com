<?php
//dirty
if (!$_GET['api']) {
    print '<html>
<head>

<title>RegMyUDID Premium Checker</title>

<link href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,700,800" rel="stylesheet" type="text/css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<link href="css/icon-effect.css" rel="stylesheet" type="text/css">	
<link href="css/settings.css" rel="stylesheet" type="text/css">
<link href="css/font-awesome.min.css" rel="stylesheet"> 
<link href="css/preloader.css" rel="stylesheet" type="text/css">
        
</head>
<body>
<div class="text-center">
<h1>RegMyUDID Premium Checker! 
<br/>A tool to verify your premium status.
<br /><a href="javascript:history.go(-1);">Go back</a> | <a href="status.php">Go to registration status checker</a></h1><hr/>
</div>
';
}
//dirty end
require_once "auto_regger/database.class.php";
require_once 'init.php';

$udid = (string) $_REQUEST['udid'];

if (!$udid)
    die('<center><form method="get"><input type="text" class="text-center" placeholder="UDID or email" size="40" name="udid" required="">
                                        <br><br />
<input type="submit" class="btn btn-info" value="Check!"></form></center>');


if (!strpos($udid, '@')) {
    $result = $DB->query_return("SELECT * FROM premium_purchases WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
} else {
    $result = $DB->query_return("SELECT * FROM premium_purchases WHERE email={$DB->sqlesc($udid)}");
    print "<h1><center>We have " . count($result) . " UDIDs with this email address, choose yours:<ceter></h1>";
    foreach ($result as $r) {
        print "<h2><center><a href=\"status_premium.php?udid={$r['udid']}\">{$r['udid']}</a></center></h2>";
    }
    die();
}


if (!$result[0]['udid'])
    die('<h1><br /><br /><center><span style="color:red;">Specified UDID is not premium.<br /> If you know that this is wrong, <br />feel free to contact us <a href="http://support.regmyudid.com/">HERE</a></span></center><br /><br /></h1><hr/><h2><center>It may take some time while we receive a response <br />from the payment gateway you choose. (up to 24h from CCNow checkout) <br />Please <a href="javascript:window.location=window.location;">Refresh page</a> in a little while.</h2></center>');

print "<h1><center>Received on: " . date('r', $result[0]['added']) . "</center></h1><br /><br />";
print "<h1><center>UDID: {$result[0]['udid']}</center></h1><br /><br />";
print "<br /><br /><h1 style='color:green;'><center>UDID is set as premium</center></h1>";
?>

</body>
</html>