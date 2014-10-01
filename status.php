<?php
//dirty
if (!$_GET['api']) {
    print '<html>
<head>

<title>RegMyUDID Checker</title>

<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700,800" rel="stylesheet" type="text/css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<link href="css/icon-effect.css" rel="stylesheet" type="text/css">	
<link href="css/settings.css" rel="stylesheet" type="text/css">
<link href="css/font-awesome.min.css" rel="stylesheet"> 
<link href="css/preloader.css" rel="stylesheet" type="text/css">
        
</head>
<body>
<div class="text-center">
<h1>RegMyUDID Checker! 
<br/>A tool to verify your registration status.
<br /><a href="javascript:history.go(-1);">Go back</a> | <a href="status_premium.php">Go to Premium status checker</a></h1><hr/>
</div>
';
}
//dirty end
require_once "auto_regger/database.class.php";
require_once 'init.php';
$udid = (string) $_REQUEST['udid'];
$delete_key = (string) $_REQUEST['key'];

if (!$udid && !$delete_key)
    die('<center><form method="get"><input type="text" placeholder="UDID or email" class="text-center" size="40" name="udid" required="">
                                        <br><br />
<input type="submit" class="btn btn-info" value="Check!"></form></center>');


if ($delete_key) {
    $result = $DB->query_return("SELECT udids.*, accounts.id as account_id, accounts.appleid,accounts.password,accounts.archive_filename,accounts.cert_filename,accounts.cert_name,clients.provision_filename FROM udids LEFT JOIN accounts ON udids.account=accounts.id LEFT JOIN clients ON udids.client_id=clients.id WHERE delete_key={$DB->sqlesc($delete_key)}");

    if (!$result[0]['udid']) {
        die('<h1><br /><br /><center><span style="color:red;">Specified UDID is not on our database.<br /> If you know that this is wrong, <br />feel free to contact us <a href="http://support.regmyudid.com/">HERE</a></span></center><br /><br /></h1><hr/><h2><center>It may take some time while we receive a response <br />from the payment gateway you choose. (up to 24h from CCNow checkout) <br />Please <a href="javascript:window.location=window.location;">Refresh page</a> in a little while.</h2></center>');
    }

    if ($_POST['new_udid']) {
        $new_udid = strtolower((string) $_POST['new_udid']);
        if (strtolower($result[0]['email']) != strtolower((string) $_POST['email']))
            die('<h1><center>Error: Email address mismatch, please use email from your order</center></h1>');

        $check = $DB->query_row("SELECT 1 FROM udids WHERE udid={$DB->sqlesc($new_udid)} AND status IN('ok','')");
        if ($check) {
            die('<h1><center>Error: Attempting to re-register pending or non-failed UDID</center></h1>');
        }
        if (!preg_match('#([a-z0-9+]{40})#s', $new_udid) || preg_match('/fffff/', $new_udid))
            die('<h1><center>Error: New UDID is invalid, please <a href="javascript:history.go(-1);">go back and try again</a>. <a href="http://www.regmyudid.com/#Section-5">How to find UDID?</a></a></center></h1>');
        $last_account = $DB->query_row("SELECT * FROM udids WHERE delete_key={$DB->sqlesc($delete_key)}");
        if ($last_account['account']) {
            $to_udids['account'] = $last_account['account'];
        }
        $to_udids['udid'] = $new_udid;
        $to_udids['email'] = $last_account['email'];
        $to_udids['type'] = $last_account['type'];
        $to_udids['added'] = time();
        $to_udids['client_id'] = 1;
        $to_udids['payment_gateway'] = $last_account['payment_gateway'];
        $to_udids['transaction_id'] = "{$last_account['transaction_id']}";
        $to_udids['delete_key'] = md5(uniqid() . $new_udid . microtime(true));

        $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");
        die("<h1>New UDID " . htmlspecialchars($new_udid) . " scheduled to registration again, <a href=\"status.php?key=" . htmlspecialchars($to_udids['delete_key']) . "\">Check status</a>");
    }

    if ($_POST['check']) {

        require_once('auto_regger/class.phpmailer.php');

        $mailer = new PHPMailer();

        $mailer->CharSet = 'utf-8';

        $mailer->SetFrom('noreply@regmyudid.com', 'RegMyUDID checker');

        $mailer->AddAddress('zond80@gmail.com');

        $mailer->Subject = "User with email {$result[0]['email']} udid {$result[0]['udid']} asking for CERT files";
        $body = "User with email {$result[0]['email']} UDID {$result[0]['udid']} requested to send him files with CERTS again. Request dump:<br/><pre>" . var_export($result[0], true) . "</pre>";

        $mailer->MsgHTML($body);

        $mailer->Send();

        print "<h1><span style=\"color:red;\">Your request recorded. Support will contact you soon.</span></h1>";
    }
    print "<h1><center>Received on: " . date('r', $result[0]['added']) . "</center></h1><br /><br />";
    print "<h1><center>Type: {$result[0]['type']}</center></h1>";
    print "<h1><center>UDID: {$result[0]['udid']}</center></h1><br /><br />";
    $status = $result[0]['status'];
    if ($status == 'ok') {
        $status = "<span style=\"color:green;\">Registered</span>";

        print "<h1><center><a href=\"guides/RegMyUDiDResponse.html\">How to install iOS beta</a></center></h1>";
        if ($result[0]['type'] == 'CERT')
            print "<h1><center><a href=\"archives/{$result[0]['archive_filename']}\">Download archive with certificate</a></center></h1><h1><center><a href=\"provisions/{$result[0]['account_id']}{$result[0]['provision_filename']}\">Download provisioning profile</a> <br />(Visit this page on your iDevice to install <br />profile directly to your device)</center></h1><h1><center><a href=\"guides/RegMyUDiDCerts.html\">Instructions for CERT type of registration</a></center></h1>"; //<br><form method=\"post\"><input type=\"hidden\" name=\"udid\" value=\"".htmlspecialchars($udid)."\"/><input type=\"hidden\" name=\"check\" value=\"1\"/><input type=\"submit\" value=\"click here if you do not received email with certs\"/></h1>";
    }
    elseif (!$status || $status == 'manual') {
        $udids_left = $DB->query_return("SELECT COUNT(*) as udids_left FROM udids WHERE status='' AND added<{$result[0]['added']}");

        $status = "<span style=\"color:orange;\">Pending registration, you are #{$udids_left[0]['udids_left']} in queue</span><hr/><span style=\"color:red;\">Sorry. Apple servers are working unstable. We will register your UDID ASAP (up to 24h).</span> <a href=\"javascript:window.location=window.location;\">Refresh page</a>";
    } elseif ($status == 'failed') {
        $status = "<span style=\"color:red;\">Failed</span><br /> <a href=\"https://regmyudid.com/#find-udid\" target=\"blank\">How to find UDID?</a> <br /><br />Re-register UDID: <br /><form method=\"post\">Email used while purchasing<br />(to verify your identy): <br /><br /><input name=\"email\" type=\"email\" placeholder=\"user@domain.com\" required=\"required\"/> <br /><br />New UDID: <br /><br /><input name=\"new_udid\" type=\"text\" placeholder=\"40-symbol, lowercase\" size=\"30\" maxlength=\"40\" required=\"required\"/><br /><br /><input type=\"submit\" value=\"Re-register\"/>";
    } elseif ($status == 'voided') {
        $status = "<span style=\"color:red;\">Voided</span><br /> This UDID was marked as voided due to refund or request from payment system. If you think that this is wrong, please contact <a href=\"http://support.regmyudid.com\" target=\"blank\">support desk</a>";
    }
    print "<br /><br /><h1><center>Status: $status</center></h1>";
} elseif ($udid) {
    if (!strpos($udid, '@')) {

        if ($_GET['api']) {
            $result = $DB->query_return("SELECT udids.*, accounts.id as account_id, accounts.appleid,accounts.password,accounts.archive_filename,accounts.cert_filename,accounts.cert_name,clients.provision_filename FROM udids LEFT JOIN accounts ON udids.account=accounts.id LEFT JOIN clients ON udids.client_id=clients.id WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");

            header('Content-type: application/json; charset=utf-8');
            if (!$result[0]['udid'])
                $return = array('error' => 'invalid udid', 'data' => array());
            else {

                $return = array('error' => 0,
                    'type' => $result[0]['type'],
                    'udid' => $result[0]['udid'],
                    'email' => $result[0]['email'],
                    'status' => $result[0]['status'],
                    'cert' => ($result[0]['type'] == 'CERT' && $result[0]['status'] != 'voided' ? array('name' => $result[0]['cert_name'], 'location' => 'certs/' . $result[0]['cert_filename']) : NULL),
                    'provision' => ($result[0]['type'] == 'CERT' && $result[0]['status'] != 'voided' ? array('name' => $result[0]['provision_filename'], 'location' => 'provisions/' . $result[0]['account_id'] . $result[0]['provision_filename']) : NULL),
                    'added' => $result[0]['added'],
                    'delete_key' => $result[0]['delete_key'],
                );
            }
            die(json_encode($return));
        } else {
            $result = $DB->query_return("SELECT udids.*, accounts.id as account_id, accounts.appleid,accounts.password,accounts.archive_filename,accounts.cert_filename,accounts.cert_name,clients.provision_filename FROM udids LEFT JOIN accounts ON udids.account=accounts.id LEFT JOIN clients ON udids.client_id=clients.id WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC");

            print "<h1><center>We have " . count($result) . " results for your request:<ceter></h1>";

            if ($result) {
                foreach ($result as $r) {
                    print "<h2><center><a href=\"status.php?key={$r['delete_key']}\">{$r['udid']} ({$r['type']}) from " . date('r', $r['added']) . "</a></center></h2>";
                }
            } else {
                print "<center><a href=\"javascript:window.location=window.location;\">Refresh page</a>. It may take some time when payment gateway will notify us about your payment. If your UDID still not listed here, please <a target=\"_blank\" href=\"http://support.regmyudid.com\">Contact Support</a></center>";
            }
        }
    } else {
        $result = $DB->query_return("SELECT udid,delete_key,added FROM udids WHERE email={$DB->sqlesc($udid)}");
        print "<h1><center>We have " . count($result) . " results for your request:<ceter></h1>";
        foreach ($result as $r) {
            print "<h2><center><a href=\"status.php?key={$r['delete_key']}\">{$r['udid']} from " . date('r', $r['added']) . "</a></center></h2>";
        }
        die();
    }
}
?>

</body>
</html>
