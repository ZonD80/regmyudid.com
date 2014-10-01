<?php
/*
 * mobile config bootrstapper, yay!
 */

require_once('functions.inc.php');
header('Content-type: application/x-apple-aspen-config; chatset=utf-8');
header('Content-Disposition: attachment; filename="isigncloud.mobileconfig"');


if ($_REQUEST['udid_only']) {
    $link = SITE_ADDRESS."/start.php?udid_only=1";
    $profile_name = 'AA App Auth';
} else {

    $encoded_ticket = htmlspecialchars((string) $_REQUEST['t']);
    $ticket = json_decode(decrypt($encoded_ticket, 'RMUSS_super_secret'), true);

    if (!$ticket)
        die('Invalid ticket');
    $link = SITE_ADDRESS.'/start.php?t=' . urlencode($encoded_ticket);
    $profile_name = $ticket['name'];
}
//$ticket['name'] = 'test';

print '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
    <dict>
        <key>PayloadContent</key>
        <dict>
            <key>URL</key>
            <string>' . $link . '</string>
            <key>DeviceAttributes</key>
            <array>
                <string>UDID</string>
            </array>
        </dict>
        <key>PayloadOrganization</key>
        <string>RegMyUDID.com</string>
        <key>PayloadDisplayName</key>
        <string>' . htmlspecialchars($profile_name) . '</string>
        <key>PayloadVersion</key>
        <integer>1</integer>
        <key>PayloadUUID</key>
        <string>9CF421B3-9853-4454-BC8A-982CBD3C907C</string>
        <key>PayloadIdentifier</key>
        <string>com.regmyudid.isigncloud</string>
        <key>PayloadDescription</key>
        <string>Tap install to begin resign</string>
        <key>PayloadType</key>
        <string>Profile Service</string>
    </dict>
</plist>';
?>