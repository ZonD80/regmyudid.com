<?php

header('Content-type: application/json; charset=utf-8');

$response = new stdClass();

$response->error = false;
$response->auth = false;
$response->data = NULL;

/**
 * Validates email
 * @param string $email
 * @return boolean
 */
function validemail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
}

function result($result) {
    die(json_encode($result));
}

require_once "../../auto_regger/database.class.php";
require_once '../../init.php';

$client_id = (int) $_GET['client_id'];
$api_password = (string) $_GET['api_password'];

if (!$client_id || !$api_password) {
    $response->error = 'Invalid client ID or API password';
    result($response);
}

$client = $DB->query_row("SELECT * FROM clients WHERE id=$client_id AND api_password={$DB->sqlesc($api_password)}");

if (!$client) {
    $response->error = 'Invalid client ID or API password';
    result($response);
}

$response->auth = true;


$mode = (string) $_GET['mode'];


if ($mode == 'set_api_info') {
    $data = (array) $_GET['data'];

    $keys_to_check = array(
        'from_name',
        'from_email',
        'support_name',
        'support_email',
        'cert_mail_file',
        'reg_mail_file',
        'failed_mail_file',
        'provision_filename',);

    foreach ($keys_to_check as $k) {
        if (!$data[$k]) {
            $response->error = "Missing $k";
            result($response);
        } else {
            $to_db[$k] = strval($data[$k]);
        }
    }


    if (!validemail($to_db['support_email']) || !validemail($to_db['from_email'])) {
        $response->error = "Invalid format in support or from emails";
        result($response);
    }


    if (!preg_match('/\.mobileprovision$/', $to_db['provision_filename'])) {
        $response->error = "Provision filename must end with .mobileprovision";
        result($response);
    }

    $check = $DB->query_row("SELECT 1 FROM clients WHERE provision_filename={$DB->sqlesc($to_db['provision_filename'])} AND id<>$client_id");
    if ($check) {
        $response->error = "Provision filename is not unique, choose another one";
        result($response);
    }

    $check = @file_get_contents($to_db['cert_mail_file']);

    if (!$check) {
        $response->error = "Can not download cert_mail_file";
        result($response);
    }

    $check = @file_get_contents($to_db['reg_mail_file']);

    if (!$check) {
        $response->error = "Can not download reg_mail_file";
        result($response);
    }

    $check = @file_get_contents($to_db['failed_mail_file']);

    if (!$check) {
        $response->error = "Can not download failed_mail_file";
        result($response);
    }

    if ($data['bcc_email']) {
        $to_db['bcc_email'] = (string) $data['bcc_email'];
        if (!validemail($to_db['bcc_email'])) {
            $response->error = "Invalid email format in bcc_email";
            result($response);
        }
    }

    if ($data['api_password']) {
        $to_db['api_password'] = (string) $data['api_password'];
    }

    $DB->query("UPDATE clients SET {$DB->build_update_query($to_db)} WHERE id={$client_id}");

    $response->data = array('success' => true, 'message' => 'API data has been updated');

    result($response);
} elseif ($mode == 'api_info') {
    $result = array('client_id' => $client['id'],
        'api_password' => $client['api_password'],
        'from_name' => $client['from_name'],
        'from_email' => $client['from_email'],
        'support_name' => $client['support_name'],
        'support_email' => $client['support_email'],
        'bcc_email' => $client['bcc_email'],
        'cert_mail_file' => $client['cert_mail_file'],
        'reg_mail_file' => $client['reg_mail_file'],
        'failed_mail_file' => $client['failed_mail_file'],
        'provision_filename' => $client['provision_filename']);

    $response->data = $result;

    result($response);
} elseif ($mode == 'delete') {
    $delete_key = (string) $_GET['delete_key'];

    $udid = $DB->query_row("SELECT * FROM udids WHERE delete_key={$DB->sqlesc($delete_key)} AND udids.client_id=$client_id");
    if ($udid['status'] != 'failed') {
        $response->error = "You can only delete failed UDIDs";
        result($response);
    }

    $check = $DB->query_row("SELECT * FROM udids WHERE transaction_id={$DB->sqlesc("from_failed_" . $udid['transaction_id'])} AND udids.client_id=$client_id");

    if (!$check) {
        $response->error = "Can not delete, there is no successfull registration with this transaction id";
        result($response);
    }
    $DB->query("DELETE FROM udids WHERE delete_key={$DB->sqlesc($delete_key)}");

    $response->data = array('success' => true, 'message' => 'UDID deleted');

    result($response);
} elseif ($mode == 'register') {


    $udid = htmlspecialchars((string) $_GET['udid']);
    $type = htmlspecialchars((string) $_GET['type']);
    $email = htmlspecialchars((string) $_GET['email']);
    $transaction_id = htmlspecialchars((string) $_GET['transaction_id']);

    if (!in_array($type, array('REG', 'CERT'))) {
        $response->error = 'Invalid registration type';
        result($response);
    }

    if (!preg_match('#^([a-z0-9+]{40})$#s', $udid) || preg_match('/fffff/', $udid)) {
        $response->error = 'Invalid UDID';
        result($response);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response->error = 'Invalid email';
        result($response);
    }

    $udid_check = $DB->query_row("SELECT type,status FROM udids WHERE udid={$DB->sqlesc($udid)} AND transaction_id={$DB->sqlesc($transaction_id)} AND udids.client_id={$DB->sqlesc($client_id)} AND status!='failed'");

    if ($udid_check) {
        $response->error = 'Attempted to register pending, ok or registering UDID with the same transaction ID';
        result($response);
    }


    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('ok','registering')");
    $certs_ok = $certs_done['certs'];

    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('')");
    $certs_wait = $certs_done['certs'];

    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('failed')");
    $certs_failed = $certs_done['certs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('ok','registering')");
    $regs_ok = $regs_done['regs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('')");
    $regs_wait = $regs_done['regs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('failed')");
    $regs_failed = $regs_done['regs'];

    if ($client['regs_total']) {
        $regs_left = $client['regs_total'] - $regs_ok - $regs_wait;

        if ($regs_left <= 0) {
            $response->error = 'No REGs left on your account. Please contact RegMyUDID.com support to buy new package.';
            result($response);
        }
    }

    if ($client['certs_total']) {
        $certs_left = $client['certs_total'] - $certs_ok - $certs_wait;
        if ($certs_left <= 0) {
            $response->error = 'No CERTs left on your account. Please contact RegMyUDID.com support to buy new package.';
            result($response);
        }
    }




    $last_account = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND transaction_id={$DB->sqlesc($transaction_id)} AND udids.client_id={$DB->sqlesc($client_id)} AND status='failed'");
    if ($last_account['account']) {
        // DELETE failed attempt    
        $DB->query("DELETE FROM udids WHERE udid={$DB->sqlesc($udid)} AND transaction_id={$DB->sqlesc($transaction_id)} AND udids.client_id={$DB->sqlesc($client_id)} AND status='failed'");
        $to_udids['account'] = $last_account['account'];
    } else {

        //$to_udids['transaction_id'] = $transaction_id;
    }
    $to_udids['udid'] = $udid;
    $to_udids['email'] = $email;
    $to_udids['type'] = $type;
    $to_udids['added'] = time();
    $to_udids['client_id'] = $client_id;
    $to_udids['payment_gateway'] = "client_{$client_id}";
    $to_udids['transaction_id'] = (string) $transaction_id;
    $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

    $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");

    $response->data = array('success' => true, 'message' => 'UDID scheduled for registering');

    result($response);
} elseif ($mode == 'auth') {
    result($response);
} elseif ($mode == 'status') {
    $udid = (string) $_GET['udid'];
    $added = (int) $_GET['added'];
    $transaction_id = (string) $_GET['transaction_id'];
    $status = (string) $_GET['status'];
    $delete_key = (string) $_GET['delete_key'];

    $start = (int) $_GET['start'];
    $limit = (int) $_GET['limit'];

    if (!$limit || $limit < 0) {
        $limit = 10;
    }

    if (!$start || $start < 0) {
        $start = 0;
    }

    $where['client'] = "udids.client_id = $client_id";

    if ($udid) {
        $where['udid'] = "(udid = {$DB->sqlesc($udid)} OR email = {$DB->sqlesc($udid)})";
    }

    if ($added) {
        $where['added'] = "added = $added";
    }

    if ($status) {
        $where['status'] = "status = {$DB->sqlesc($status)}";
    }

    if ($delete_key) {
        $where['delete_key'] = "delete_key = {$DB->sqlesc($delete_key)}";
    }

    if ($transaction_id) {
        $where['transaction'] = "transaction_id = {$DB->sqlesc($transaction_id)}";
    }

    $where = "WHERE " . implode(' AND ', $where);

    $result = $DB->query_return("SELECT udids.*, accounts.id as account_id, accounts.appleid,accounts.password,accounts.archive_filename,accounts.cert_filename,accounts.cert_name,clients.provision_filename FROM udids LEFT JOIN accounts ON udids.account=accounts.id LEFT JOIN clients ON udids.client_id=clients.id $where ORDER BY added DESC LIMIT $start,$limit");

    if (!$result[0]['udid']) {
        $response->error = 'Invalid UDID or email or UDID does not belong to you';
        result($response);
    } else {

        foreach ($result as $u) {
            $udids_left = 0;
            if (!$u['status']) {
                $udids_left = $DB->query_row("SELECT COUNT(*) as udids_left FROM udids WHERE status='' AND added<{$u['added']}");
                $udids_left = $udids_left['udids_left'];
            }
            $return[] = array(
                'type' => $u['type'],
                'udid' => $u['udid'],
                'email' => $u['email'],
                'transaction_id' => $u['transaction_id'],
                'status' => (!$u['status'] ? 'pending' : $u['status']),
                'cert' => ($u['type'] == 'CERT' ? array('name' => $u['cert_name'], 'location' => 'certs/' . $u['cert_filename']) : NULL),
                'provision' => ($u['type'] == 'CERT' ? array('name' => $u['provision_filename'], 'location' => 'provisions/' . $u['account_id'] . $u['provision_filename']) : NULL),
                'added' => $u['added'],
                'registered_at' => $u['registered_at'],
                'delete_key' => $u['delete_key'],
                'transaction_id' => $u['transaction_id'],
                'queue_no' => $udids_left,
            );
        }
    }

    $response->data = $return;

    result($response);
} elseif ($mode == 'statistics') {
    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('ok','registering')");
    $certs_ok = $certs_done['certs'];

    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('')");
    $certs_wait = $certs_done['certs'];

    $certs_done = $DB->query_row("SELECT COUNT(*) AS certs FROM udids WHERE udids.client_id=$client_id AND type='CERT' AND status IN ('failed')");
    $certs_failed = $certs_done['certs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('ok','registering')");
    $regs_ok = $regs_done['regs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('')");
    $regs_wait = $regs_done['regs'];

    $regs_done = $DB->query_row("SELECT COUNT(*) AS regs FROM udids WHERE udids.client_id=$client_id AND type='REG' AND status IN ('failed')");
    $regs_failed = $regs_done['regs'];

    if ($client['regs_total'])
        $regs_left = $client['regs_total'] - $regs_ok - $regs_wait - $regs_failed;
    else
        $regs_left = 'unlimited';

    if ($client['certs_total'])
        $certs_left = $client['certs_total'] - $certs_ok - $certs_wait - $certs_failed;
    else
        $certs_left = 'unlimited';

    $result = array(
        'certs' => array('ok' => $certs_ok, 'pending' => $certs_wait, 'failed' => $certs_failed, 'free' => $certs_left),
        'regs' => array('ok' => $regs_ok, 'pending' => $regs_wait, 'failed' => $regs_failed, 'free' => $regs_left),
    );

    $response->data = $result;

    result($response);
}

$response->error = 'Invalid mode';
result($response);
?>