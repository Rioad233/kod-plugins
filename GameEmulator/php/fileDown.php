<?php
header("Content-type:text/html;charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");//这里“Access-Token”是我要传到后台的内容key
header("Access-Control-Expose-Headers: *");
if($_SERVER['REQUEST_METHOD']=='HEAD'){
    return "1";
}
$url = $_GET['url'];
$KOD_SESSION_ID = $_GET['KOD_SESSION_ID'];
$CSRF_TOKEN = $_GET['CSRF_TOKEN'];
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_POST, 0);
curl_setopt($curl, CURLOPT_COOKIE," CSRF_TOKEN=".$CSRF_TOKEN."; KOD_SESSION_ID=".$KOD_SESSION_ID.";");
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$res = curl_exec($curl);
curl_errno($curl);
curl_close($curl);
header('Content-Type: '.curl_getinfo($curl,CURLINFO_CONTENT_TYPE));
header('Content-Disposition: attachment; filename="";');
echo $res;
