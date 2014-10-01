<?php

//file_put_contents('log.txt', var_export($_REQUEST, true) . "\n\n", FILE_APPEND);

function ipn_query($url, $data) {

    // generate the POST data string
    $post_data = http_build_query($data, '', '&');



    // our curl handle (initialize if required)
    static $ch = null;
    if (is_null($ch)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RMU; hash:14832907ufdfhlqhr12lrhfdkjf');
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    // run the query
    $res = curl_exec($ch);

    //var_dump($res);
}

/**
 * Returns the url query as associative array 
 * 
 * @param    string    query 
 * @return    array    params 
 */
function convertUrlQuery($query) {
    $queryParts = explode('&', $query);

    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }

    return $params;
}

function encrypt($text, $salt) {
    if (!$text)
        return false;
    return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
}

function decrypt($text, $salt) {
    if (!$text)
        return false;
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND)));
}

require_once "auto_regger/database.class.php";
require_once 'init.php';


//configuration here
require_once('ipn.init.php');
//end

$from = $_REQUEST['from'];


if ($from == 'webtastico') {
    $ipn_password = (string) $_POST['ipn_password'];
    if ($ipn_password != $CONFIG['IPN_PASSWORD']) {
        die('invalid ipn password');
    }
    $payer_email = htmlspecialchars((string) $_POST['email']);

    $type = htmlspecialchars($_POST['type']);

    $transaction_id = htmlspecialchars(((string) $_POST['transaction_id']));

    $gateway_response = (((string) $_POST['gw_response']));

    /*
     * PREMIUM HANDLING
     */

    if ($type == 'PREMIUM') {
        $udids = json_decode((string) $_POST['udids'], true);
        foreach ($udids as $udid) {
            if ($udid) {
                $to_db['udid'] = $udid;
                $to_db['email'] = $payer_email;
                $to_db['added'] = time();
                $to_db['transaction_id'] = $transaction_id;
                $to_db['gateway_response'] = $gateway_response;
                $DB->query('INSERT INTO premium_purchases ' . $DB->build_insert_query($to_db) . ' ON DUPLICATE KEY UPDATE ' . $DB->build_update_query($to_db));
            }
        }
        $to_ipn['email'] = $payer_email;
        $to_ipn['days'] = 365;
        $to_ipn['transaction_id'] = $transaction_id;
        //sending mail here
        require_once('auto_regger/class.phpmailer.php');

        $mailer = new PHPMailer();

        $mailer->CharSet = 'utf-8';

        $mailer->SetFrom('noreply@regmyudid.com', "RegMyUDID");

        $mailer->AddAddress($payer_email);

        $mailer->Subject = "Premium package has been successfully activated!";

        $body = @file_get_contents('EMAILS/response_premium.html');
        $body = str_replace('{email}', $payer_email, $body);
        $body = str_replace('{udids}', @implode('<br>', $udids), $body);

        $mailer->MsgHTML($body);

        $mailer->Send();
        //mail sending end
        ipn_query('https://www.appaddict.org/premium.php?from=rmu', $to_ipn);
        die('ok;');
    }
    $udid = htmlspecialchars(strtolower((string) $_POST['udid']));

    $udid = str_replace('ffffff', 'IOS7', $udid);


    if (!$udid) {
        $udid = 'none-' . uniqid();
    }

    $voided = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='voided' ORDER BY added DESC LIMIT 1");


    if ($voided) {
        if ($voided['type'] == $type) {
            $to_udids['udid'] = $udid;
            $to_udids['email'] = $payer_email;
            $to_udids['type'] = $type;
            $to_udids['added'] = time();
            $to_udids['client_id'] = 1;
            $to_udids['payment_gateway'] = 'stripe';
            $to_udids['transaction_id'] = $transaction_id;
            $to_udids['gateway_response'] = $gateway_response;
            $to_udids['status'] = 'ok';
            $DB->query("UPDATE udids SET {$DB->build_update_query($to_udids)} WHERE delete_key={$DB->sqlesc($voided['delete_key'])}");
            die("ok\nvoided set as ok");
        }
    }

    $last_account = $DB->query_row("SELECT account FROM udids WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
    if ($last_account['account']) {
        $to_udids['account'] = $last_account['account'];
    }
    $to_udids['udid'] = $udid;
    $to_udids['email'] = $payer_email;
    $to_udids['type'] = $type;
    $to_udids['added'] = time();
    $to_udids['client_id'] = 1;
    $to_udids['payment_gateway'] = 'stripe';
    $to_udids['transaction_id'] = $transaction_id;
    $to_udids['gateway_response'] = $gateway_response;
    $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

    $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");

    die('ok');
} elseif ($from == 'stripe') {
    // NO PREMIUM HANDLING HERE
    require 'stripe_libs/Stripe.php';

    if ($_POST) {
        Stripe::setApiKey("sk_live_ivfgFFZzhuzzw1MMLx9t92Wk");

        try {
            if (!isset($_POST['stripeToken']))
                throw new Exception("The Stripe Token was not generated correctly");

            $type = (string) $_POST['type'];

            if (!in_array($type, array('REG', 'CERT')))
                die('Invalid type of registration');
            $result = Stripe_Charge::create(array("amount" => $CONFIG[$type . '_PRICE'] * 100,
                        "currency" => "gbp",
                        "card" => $_POST['stripeToken']));

            $payer_email = ($result->card->name);

            $udid = strtolower((string) $_POST['udid']);

            $udid = str_replace('ffffff', 'IOS7', $udid);


            if (!$udid) {
                $udid = 'none-' . uniqid();
            }

            $transaction_id = $result->id;

            $gateway_response = var_export($result, true);

            $voided = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='voided' ORDER BY added DESC LIMIT 1");


            if ($voided) {
                if ($voided['type'] == $type) {
                    $to_udids['udid'] = $udid;
                    $to_udids['email'] = $payer_email;
                    $to_udids['type'] = $type;
                    $to_udids['added'] = time();
                    $to_udids['client_id'] = 1;
                    $to_udids['payment_gateway'] = 'stripe';
                    $to_udids['transaction_id'] = $transaction_id;
                    $to_udids['gateway_response'] = $gateway_response;
                    $to_udids['status'] = 'ok';
                    $DB->query("UPDATE udids SET {$DB->build_update_query($to_udids)} WHERE delete_key={$DB->sqlesc($voided['delete_key'])}");
                    die("ok\nvoided set as ok");
                }
            }


            $last_account = $DB->query_row("SELECT account FROM udids WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
            if ($last_account['account']) {
                $to_udids['account'] = $last_account['account'];
            }
            $to_udids['udid'] = $udid;
            $to_udids['email'] = $payer_email;
            $to_udids['type'] = $type;
            $to_udids['added'] = time();
            $to_udids['client_id'] = 1;
            $to_udids['payment_gateway'] = 'stripe';
            $to_udids['transaction_id'] = $transaction_id;
            $to_udids['gateway_response'] = $gateway_response;
            $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

            $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");

            print '<h1>Your payment was successful. Your UDID has been scheduled for registration. <a href="status.php?udid=' . htmlspecialchars($udid) . '">Check status</a></h1>';
        } catch (Exception $e) {
            $error = $e->getMessage();
            print $error;
        }
    }
    die();
} elseif ($from == 'paypal') {

    $udid = strtolower((string) $_POST['custom']);

    $transaction_id = (string) $_POST['txn_id'];


    $gateway_response = var_export($_POST, true);
    $payment_status = (string) $_POST['payment_status'];
    $price = (float) $_POST['mc_gross'];
    $currency = (string) $_POST['mc_currency'];
    $receiver_email = strtolower((string) $_POST['receiver_email']);
    $payer_email = (string) $_POST['payer_email'];

    /*
     * PREMIUM HANDLING
     */

    if ($price == $CONFIG['PREMIUM_PRICE']) {

        //file_put_contents('log.txt', var_export($_REQUEST,true));
        // read the data send by PayPal
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes((string) $value));
            $req .= "&$key=$value";
        }

// post back to PayPal system to validate
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Host: www.paypal.com:443\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

        $fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
        if (!$fp) {
            die('unable to open socket to paypal');
        } else {
            fputs($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets($fp, 1024);
                //file_put_contents('log.txt', $res);
            }
            fclose($fp);
        }


        if (strcmp($res, "VERIFIED") == 0) {
// VALID PAYMENT
            if ($receiver_email != strtolower($CONFIG['RECEIVER_EMAIL']))
                die('invalid transaction');
            elseif ($payment_status == 'Completed') {

                $data = json_decode($udid, true);
                $udids = $data['udids'];
                $AA_email = $data['email'];

                foreach ($udids as $udid) {
                    if ($udid) {
                        $to_db['udid'] = $udid;
                        $to_db['email'] = $payer_email;
                        $to_db['added'] = time();
                        $to_db['transaction_id'] = $transaction_id;
                        $to_db['gateway_response'] = $gateway_response;
                        $DB->query('INSERT INTO premium_purchases ' . $DB->build_insert_query($to_db) . ' ON DUPLICATE KEY UPDATE ' . $DB->build_update_query($to_db));
                    }
                }
                $to_ipn['email'] = $AA_email;
                $to_ipn['days'] = 365;
                $to_ipn['transaction_id'] = $transaction_id;
                //sending mail here
                require_once('auto_regger/class.phpmailer.php');

                $mailer = new PHPMailer();

                $mailer->CharSet = 'utf-8';

                $mailer->SetFrom('noreply@regmyudid.com', "RegMyUDID");

                $mailer->AddAddress($payer_email);

                $mailer->Subject = "Premium package has been successfully activated!";

                $body = @file_get_contents('EMAILS/response_premium.html');
                $body = str_replace('{email}', $AA_email, $body);
                $body = str_replace('{udids}', @implode('<br>', $udids), $body);

                $mailer->MsgHTML($body);

                $mailer->Send();
                //mail sending end
                ipn_query('https://www.appaddict.org/premium.php?from=rmu', $to_ipn);
                die('ok;');
            }
        }
    }
    $udid = str_replace('ffffff', 'IOS7', $udid);

    $paid = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND transaction_id={$DB->sqlesc($transaction_id)}");


    if ($paid) {
        die('ok\nalready processed transaction');
    }
// read the data send by PayPal
    $req = 'cmd=_notify-validate';
    foreach ($_POST as $key => $value) {
        $value = urlencode(stripslashes((string) $value));
        $req .= "&$key=$value";
    }

// post back to PayPal system to validate
    $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Host: www.paypal.com:443\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

    $fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
    if (!$fp) {
        die('unable to open socket to paypal');
    } else {
        fputs($fp, $header . $req);
        while (!feof($fp)) {
            $res = fgets($fp, 1024);
            //file_put_contents('log.txt', $res);
        }
        fclose($fp);


        if (strcmp($res, "VERIFIED") == 0) {
// VALID PAYMENT
            if ($receiver_email != strtolower($CONFIG['RECEIVER_EMAIL']))
                die('invalid transaction');
            elseif ($payment_status == 'Completed') {
//set gateway response and udid
                if ($price == $CONFIG['REG_PRICE'])
                    $type = 'REG';
                elseif ($price == $CONFIG['CERT_PRICE'])
                    $type = 'CERT';
                else
                    die('invalid price');

                $voided = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='voided' ORDER BY added DESC LIMIT 1");


                if ($voided) {
                    if ($voided['type'] == $type) {
                        $to_udids['udid'] = $udid;
                        $to_udids['email'] = $payer_email;
                        $to_udids['type'] = $type;
                        $to_udids['added'] = time();
                        $to_udids['client_id'] = 1;
                        $to_udids['payment_gateway'] = 'paypal';
                        $to_udids['transaction_id'] = $transaction_id;
                        $to_udids['gateway_response'] = $gateway_response;
                        $to_udids['status'] = 'ok';
                        $DB->query("UPDATE udids SET {$DB->build_update_query($to_udids)} WHERE delete_key={$DB->sqlesc($voided['delete_key'])}");
                        die("ok\nvoided set as ok");
                    }
                }

                if (!$udid)
                    $udid = 'empty-' . md5(microtime(true) + rand(0, 65535));


                $last_account = $DB->query_row("SELECT account FROM udids WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
                if ($last_account['account']) {
                    $to_udids['account'] = $last_account['account'];
                }
                $to_udids['udid'] = $udid;
                $to_udids['email'] = $payer_email;
                $to_udids['type'] = $type;
                $to_udids['added'] = time();
                $to_udids['client_id'] = 1;
                $to_udids['payment_gateway'] = 'paypal';
                $to_udids['transaction_id'] = $transaction_id;
                $to_udids['gateway_response'] = $gateway_response;
                $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

                $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");
            }
        } elseif (strcmp($res, "INVALID") == 0) {
            die('invalid transaction got from paypal');
        }
    }
} elseif ($from == 'dalpay') {
    print "ok\n";
    if ($_REQUEST['NotificationPassword'] != 'qwertyytrewq') {
        die('invalid IPN from dalpay');
    }

    $udid = strtolower($_REQUEST['user1']);

    $transaction_id = (string) $_REQUEST['order_num'];
    $payer_email = (string) $_REQUEST['cust_email'];
    $price = (string) $_REQUEST['item1_price'];

    $gateway_response = var_export($_REQUEST, true);
    if ($_REQUEST['status'] == 'accepted') {


        /*
         * PREMIUM HANDLING
         */

        if ($price == $CONFIG['PREMIUM_PRICE']) {
            $data = json_decode($udid, true);
            $udids = $data['udids'];
            $AA_email = $data['email'];

            foreach ($udids as $udid) {
                if ($udid) {
                    $to_db['udid'] = $udid;
                    $to_db['email'] = $payer_email;
                    $to_db['added'] = time();
                    $to_db['transaction_id'] = $transaction_id;
                    $to_db['gateway_response'] = $gateway_response;
                    $DB->query('INSERT INTO premium_purchases ' . $DB->build_insert_query($to_db) . ' ON DUPLICATE KEY UPDATE ' . $DB->build_update_query($to_db));
                }
            }
            $to_ipn['email'] = $AA_email;
            $to_ipn['days'] = 365;
            $to_ipn['transaction_id'] = $transaction_id;
            //sending mail here
            require_once('auto_regger/class.phpmailer.php');

            $mailer = new PHPMailer();

            $mailer->CharSet = 'utf-8';

            $mailer->SetFrom('noreply@regmyudid.com', "RegMyUDID");

            $mailer->AddAddress($payer_email);

            $mailer->Subject = "Premium package has been successfully activated!";

            $body = @file_get_contents('EMAILS/response_premium.html');
            $body = str_replace('{email}', $AA_email, $body);
            $body = str_replace('{udids}', @implode('<br>', $udids), $body);

            $mailer->MsgHTML($body);

            $mailer->Send();
            //mail sending end
            ipn_query('https://www.appaddict.org/premium.php?from=rmu', $to_ipn);
            die('ok;');
        }
        $udid = str_replace('ffffff', 'IOS7', $udid);

        $paid = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND transaction_id={$DB->sqlesc($transaction_id)}");


        if ($paid) {
            die('ok\nalready processed transaction');
        }
        if (!$udid)
            $udid = 'empty-' . md5(microtime(true) + rand(0, 65535));

        if ($price == $CONFIG['REG_PRICE'])
            $type = 'REG';
        elseif ($price == $CONFIG['CERT_PRICE'])
            $type = 'CERT';
        else
            die('invalid price');



        $voided = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='voided' ORDER BY added DESC LIMIT 1");


        if ($voided) {
            if ($voided['type'] == $type) {
                $to_udids['udid'] = $udid;
                $to_udids['email'] = $payer_email;
                $to_udids['type'] = $type;
                $to_udids['added'] = time();
                $to_udids['client_id'] = 1;
                $to_udids['payment_gateway'] = 'dalpay';
                $to_udids['transaction_id'] = $transaction_id;
                $to_udids['gateway_response'] = $gateway_response;
                $to_udids['status'] = 'ok';
                $DB->query("UPDATE udids SET {$DB->build_update_query($to_udids)} WHERE delete_key={$DB->sqlesc($voided['delete_key'])}");
                die("ok\nvoided set as ok");
            }
        }

        $last_account = $DB->query_row("SELECT account FROM udids WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
        if ($last_account['account']) {
            $to_udids['account'] = $last_account['account'];
        }
        $to_udids['udid'] = $udid;
        $to_udids['email'] = $payer_email;
        $to_udids['type'] = $type;
        $to_udids['added'] = time();
        $to_udids['client_id'] = 1;
        $to_udids['payment_gateway'] = 'dalpay';
        $to_udids['transaction_id'] = $transaction_id;
        $to_udids['gateway_response'] = $gateway_response;
        $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

        $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");
    } elseif ($_REQUEST['status'] == 'voided') {
        $DB->query("UPDATE udids SET status='voided' WHERE udid={$DB->sqlesc($udid)}");
    }
} elseif ($from == 'ccnow') {
    // NO PREMIUM HANDLING HERE
    print "ok\n";
    //file_put_contents('log.txt', var_export($_REQUEST, true));



    $udid = strtolower($_REQUEST['x_product_option_value_1_1']);
    $udid = str_replace('ffffff', 'IOS7', $udid);


    $transaction_id = $_REQUEST['x_orderid'];

    if ($_REQUEST['x_status'] == 'pending') {

        $paid = $DB->query_return("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)}");
        if ($paid[0]['transaction_id'] == $transaction_id) {
            die('ok\nalready processed transaction');
        }
        if (!$udid)
            $udid = 'empty-' . md5(microtime(true) + rand(0, 65535));
        $payer_email = $_REQUEST['x_email'];

        $udid = str_replace('ffffff', 'IOS7', $udid);

        $product = $_REQUEST['x_product_sku_1'];
        if ($product == 'SA-001')
            $type = 'REG';
        elseif ($product == 'AA-002')
            $type = 'CERT';
        else
            die("ok\ninvalid product");
        $gateway_response = var_export($_POST, true);

        $voided = $DB->query_row("SELECT * FROM udids WHERE udid={$DB->sqlesc($udid)} AND status='voided' ORDER BY added DESC LIMIT 1");


        if ($voided) {
            if ($voided['type'] == $type) {
                $to_udids['udid'] = $udid;
                $to_udids['email'] = $payer_email;
                $to_udids['type'] = $type;
                $to_udids['added'] = time();
                $to_udids['client_id'] = 1;
                $to_udids['payment_gateway'] = 'ccnow';
                $to_udids['transaction_id'] = $transaction_id;
                $to_udids['gateway_response'] = $gateway_response;
                $to_udids['status'] = 'ok';
                $DB->query("UPDATE udids SET {$DB->build_update_query($to_udids)} WHERE delete_key={$DB->sqlesc($voided['delete_key'])}");
                die("ok\nvoided set as ok");
            }
        }
        $last_account = $DB->query_row("SELECT account FROM udids WHERE udid={$DB->sqlesc($udid)} ORDER BY added DESC LIMIT 1");
        if ($last_account['account']) {
            $to_udids['account'] = $last_account['account'];
        }
        $to_udids['udid'] = $udid;
        $to_udids['email'] = $payer_email;
        $to_udids['type'] = $type;
        $to_udids['added'] = time();
        $to_udids['client_id'] = 1;
        $to_udids['payment_gateway'] = 'ccnow';
        $to_udids['transaction_id'] = $transaction_id;
        $to_udids['gateway_response'] = $gateway_response;
        $to_udids['delete_key'] = md5(uniqid() . $udid . microtime(true));

        $DB->query("INSERT INTO udids {$DB->build_insert_query($to_udids)}");
// mark order as shipped automatically in CCnow
        $url = 'https://www.ccnow.com/cgi-local/oapi_receive.cgi';
        $data = array('x_clientid' => 'regmyudid', 'x_password' => 'qwertyytrewq', 'x_orderid_1' => $transaction_id, 'x_action_1' => 'ship', 'x_notes_1' => 'You can check status on https://regmyudid.com/status.php?udid=' . $udid);

// use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
    }
}
die('ok');
?>