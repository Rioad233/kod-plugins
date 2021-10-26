<?php
$url = $_GET['url'];
$fileName = $_GET['fileName'];
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_POST, 0);
curl_setopt($curl, CURLOPT_REFERER, 'https://www.aliyundrive.com/');
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$res = curl_exec($curl);
curl_errno($curl);
curl_close($curl);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$fileName.'";');
echo $res;
