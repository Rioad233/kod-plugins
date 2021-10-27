<?php
header("Content-type:text/html;charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");//这里“Access-Token”是我要传到后台的内容key
header("Access-Control-Expose-Headers: *");
require_once "cache.php";
$type = $_GET['type'];
if($type == "cookie"){
    setGameCookie($_COOKIE);
    echo "";
}else if($type == "file"){
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="1.txt";');
    if($_SERVER['REQUEST_METHOD']=='OPTIONS' || $_SERVER['REQUEST_METHOD']=='HEAD'){
        header('content-length: 1');
        return;
    }
    $url = $_GET['url'];
    $cookies = getGameCookie();
    $cookieStr = "";
    foreach($cookies as $key => $cookie){
        $oneStr = $cookie;
        if(is_array($cookie)){
            $oneStr = json_encode($cookie);
        }
        $cookieStr = " ".$cookieStr.$key."=".urlencode($oneStr).";";
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_COOKIE,$cookieStr);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 1000);
    $res = curl_exec($curl);
    curl_errno($curl);
    curl_close($curl);
    echo $res;
}

