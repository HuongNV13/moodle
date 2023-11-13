<?php
require_once('../config.php');

$password = 'akfkpijgtaydiidn';

//Establishing connection
$url = "{imap.gmail.com:993/imap/ssl/novalidate-cert}Test IMAP";
$id = 'huongn@moodle.com';
$pwd = 'akfkpijgtaydiidn';
$imap = imap_open($url, $id, $pwd);
print("Connection established...."."<br>");
$messages = imap_search($imap, 'UNSEEN UNFLAGGED');
echo "<pre>";
foreach ($messages as $messageid) {
    $header = imap_fetchheader($imap, $messageid);
    preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)\r\n/m', $header, $matches);
    $headers = array_combine($matches[1], $matches[2]);
    //var_dump($structure);
    //$data = imap_fetchheader($imap, $messageid, '1.1', FT_PEEK);
    var_dump($headers);
    //foreach ($structure->parts as $partno => $part) {
    //    //$data = imap_fetchbody($this->client, $messageid, '1.1');
    //    if (isset($part->id)) {
    //        var_dump(htmlentities($part->id));
    //    }
    //    var_dump($part);
    //    if (strtolower($part->subtype) == 'html') {
    //        var_dump($data);
    //    }
    //}
    //var_dump(imap_headerinfo($imap, $messageid));
    //var_dump(imap_fetchstructure($imap, $messageid));
    //var_dump(imap_fetchmime($imap, $messageid, 1));
    //var_dump(imap_fetchbody($imap, $messageid, 2));
}
//var_dump($messages);
exit();
//Searching emails
$emailData = imap_search($imap, '');

if (! empty($emailData)) {
    foreach ($emailData as $msg) {
        $msg = imap_fetchbody($imap, $msg, "1");
        print(quoted_printable_decode($msg)."<br>");
    }
}
//Closing the connection
imap_close($imap);
