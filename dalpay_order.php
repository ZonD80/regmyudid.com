<!--success-->
<link href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,700,800" rel="stylesheet" type="text/css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<link href="css/icon-effect.css" rel="stylesheet" type="text/css">	
<link href="css/settings.css" rel="stylesheet" type="text/css">
<link href="css/font-awesome.min.css" rel="stylesheet"> 
<link href="css/preloader.css" rel="stylesheet" type="text/css">
<h1><center>Thank you for your order!</center></h1>
<br />

<h2><center>You can check your order here:</center></h2> <br /><br /><h4><center>
        <?php
        $premium_data = json_decode((string) $_REQUEST['user1'], true);
        if ($premium_data) {
            foreach ($premium_data['udids'] as $udid) {
                print '<a target="_blank" href="https://regmyudid.com/status_premium.php?udid=' . $udid . '">Check status of ' . $udid . '</a><br/>';
            }
        } else {
            print '<a target="_blank" href="https://regmyudid.com/status.php?udid=' . $udid . '">Check status of ' . $udid . '</a><br/>';
        }
        ?>
    </center></h4><br/>
<h3><center>Your purchase is subject to the RegMyUDiD <a href="https://regmyudid.com/#terms">terms and conditions</a></center></h3>
