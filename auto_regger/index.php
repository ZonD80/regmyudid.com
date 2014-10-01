<?php

function gunzip($zipped) {
    $offset = 0;
    if (substr($zipped, 0, 2) == "\x1f\x8b")
        $offset = 2;
    if (substr($zipped, $offset, 1) == "\x08") {
        # file_put_contents("tmp.gz", substr($zipped, $offset - 2));
        return gzinflate(substr($zipped, $offset + 8));
    }
    return "Unknown Format";
}

function result($data = null) {
    die(json_encode($data));
}

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

require_once "database.class.php";
require_once '../init.php';
$action = (string) $_REQUEST['action'];

if ($action == 'udids_to_reg') {
    $result = $DB->query_return("SELECT email,udid,type,account,added,delete_key FROM udids WHERE status='' ORDER BY added ASC LIMIT 1");
    if ($result[0]) {
        $locked_accounts = $DB->query_return("SELECT GROUP_CONCAT(account) AS locked FROM udids WHERE status='registering'");
        $locked_accounts = $locked_accounts[0]['locked'];
        $res = $DB->query_return("SELECT * FROM accounts WHERE used_slots<100" . ($result[0]['account'] ? " AND id<>{$result[0]['account']}" : '') . ($locked_accounts ? " AND id NOT IN($locked_accounts)" : '') . " ORDER BY used_slots ASC LIMIT 1");
        if (!$res[0])
            result();
        $result[0]['appleid'] = $res[0]['appleid'];
        $result[0]['password'] = $res[0]['password'];
        $result[0]['profile_name'] = $res[0]['profile_name'];
        $result[0]['REG_substring'] = $res[0]['REG_substring'];
        $result[0]['CERT_substring'] = $res[0]['CERT_substring'];

        $DB->query("UPDATE accounts SET used_slots=used_slots+1 WHERE id={$res[0]['id']}");
        $DB->query("UPDATE udids SET status='registering',account={$res[0]['id']} WHERE delete_key={$DB->sqlesc($result[0]['delete_key'])}");

        if ($res[0]['used_slots'] >= 90) {
            // notification about used slots
            require_once('class.phpmailer.php');

            $mailer = new PHPMailer();

            $mailer->CharSet = 'utf-8';

            $mailer->SetFrom('noreply@regmyudid.com', "RegMyUDID ALERT {$res[0]['used_slots']} slots used on {$res[0]['appleid']}");

            $mailer->AddAddress('d.kilgallon@me.com');

            $mailer->Subject = "{$res[0]['used_slots']} slots are used on {$res[0]['appleid']}!";

            $body = "<h1>what the fuck man, {$res[0]['used_slots']} slots are are used on {$res[0]['appleid']}! PLEASE HIRE UP ADD MORE ACCOUNTS MAN. PEOPLE ARE WAITING</h1>";

            $mailer->MsgHTML($body);

            $mailer->Send();
        }
        result($result[0]);
    } else
        result();
}
elseif ($action == 'complete_register') {
    $udid = (string) $_REQUEST['udid'];
    $added = (int) $_REQUEST['added'];
    $delete_key = (string) $_REQUEST['delete_key'];

    $DB->query("UPDATE udids SET status='ok',registered_at=" . time() . " WHERE delete_key={$DB->sqlesc($delete_key)}");
    $emailar = $DB->query_return("SELECT udids.email,udids.type,udids.account, accounts.archive_filename,clients.* FROM udids LEFT JOIN accounts ON udids.account=accounts.id LEFT JOIN clients ON udids.client_id=clients.id WHERE delete_key={$DB->sqlesc($delete_key)}");

    $email = $emailar[0]['email'];
    $type = $emailar[0]['type'];
    $archive = $emailar[0]['archive_filename'];
    $from_name = $emailar[0]['from_name'];
    $from_email = $emailar[0]['from_email'];
    $reg_mail_file = $emailar[0]['reg_mail_file'];
    $cert_mail_file = $emailar[0]['cert_mail_file'];
    $provision_filename = $emailar[0]['provision_filename'];
    $account_id = $emailar[0]['account'];


    require_once('class.phpmailer.php');

    $mailer = new PHPMailer();

    $mailer->CharSet = 'utf-8';

    $mailer->SetFrom($from_email, $from_name);

    $mailer->AddAddress($email);

    if ($type == 'REG') {
        $mailer->Subject = "Your UDID $udid successfully registered!";
        $body = @file_get_contents($reg_mail_file);
    } elseif ($type == 'CERT') {
        $mailer_udid = new PHPMailer();

        $mailer_udid->CharSet = 'utf-8';

        $mailer_udid->SetFrom($from_email, $from_name);

        $mailer_udid->AddAddress($email);
        $mailer_udid->AddBcc($emailar[0]['bcc_email']);

        $mailer_udid->Subject = "Your UDID $udid successfully registered!";
        $body = @file_get_contents($reg_mail_file);
        $mailer_udid->MsgHTML($body);

        $mailer_udid->Send();


        $mailer->Subject = "Advanced UDID activation - UDID $udid activated!";
        $body = @file_get_contents($cert_mail_file);

        $mailer->AddBcc($emailar[0]['bcc_email']);

        $provision = base64_decode($_POST['provision']);
        $PROV_FILE = 'provisions/' . $account_id . $provision_filename;
        file_put_contents('../' . $PROV_FILE, $provision);
        $ARCH_FILE = 'archives/' . $archive;

        $body = str_replace('{UDID}', $udid, $body);
    }

    $mailer->MsgHTML($body);

    $mailer->Send();

    result();
} elseif ($action == 'failed_register') {


    $udid = (string) $_REQUEST['udid'];
    $added = (int) $_REQUEST['added'];
    $delete_key = (string) $_REQUEST['delete_key'];
    $DB->query("UPDATE udids SET status='failed',registered_at=" . time() . " WHERE delete_key={$DB->sqlesc($delete_key)}");

    $emailar = $DB->query_return("SELECT udids.email,udids.account, clients.* FROM udids LEFT JOIN clients ON udids.client_id=clients.id WHERE delete_key={$DB->sqlesc($delete_key)}");

    $email = $emailar[0]['email'];
    $support_name = $emailar[0]['support_name'];
    $support_email = $emailar[0]['support_email'];
    $failed_mail_file = $emailar[0]['failed_mail_file'];

    // update account used_slots counter
    if ($emailar[0]['account'])
        $DB->query("UPDATE accounts SET used_slots=used_slots-1 WHERE id={$emailar[0]['account']}");

    require_once('class.phpmailer.php');

    $mailer = new PHPMailer();

    $mailer->CharSet = 'utf-8';

    $mailer->SetFrom($support_email, $support_name);

    $mailer->AddAddress($email);

    $mailer->AddBcc($emailar[0]['bcc_email']);

    $mailer->Subject = "Registration $udid failed!";

    $body = @file_get_contents($failed_mail_file);

    $body = str_replace('{EMAIL}', urlencode($email), $body);

    $mailer->MsgHTML($body);

    $mailer->Send();
    result();
}
die('unknown query');
?>