<?php
header("Content-type:text/html;charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");//这里“Access-Token”是我要传到后台的内容key
header("Access-Control-Expose-Headers: *");
require_once "cache.php";
if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
echo return_data(false,"不支持OPTIONS请求","");
return;
}
$fileIo = file_get_contents('php://input');
$data = json_decode($fileIo,true);
if($_GET["type"] == "login"){
    //登录 {id:"",username:"",password:""}
    if(empty($data["id"]) && (empty($data["username"]) || empty($data["password"]))){
        echo return_data(false,"用户登录失败","");
        return;
    }
    $user=userSelect($data);
    if(empty($user)){
        echo return_data(false,"用户登录失败","");
        return;
    }
    $user["password"] = "";
    echo return_data(true,"用户登录成功",$user);
}else if($_GET["type"] == "reg"){
    //注册 {username:"",password:""}
    if(empty($data["username"]) || empty($data["password"])){
        echo return_data(false,"用户名密码不可为空","");
        return;
    }
    $user = userSelectByUserName($data["username"]);
    if(!empty($user)){
        echo return_data(false,"当前用户已被注册","");
        return;
    }
    $db = array(
    "id"=>md5(time()."-".rand(1000,9999)),
    "username"=>$data["username"],
    "password"=>$data["password"]
    );
    userInsertUpdate($db);
    $db = userSelectById($db["id"]);
    if(empty($db)){
        echo return_data(false,"注册失败","");
        return;
    }
    $db["password"] = "";
    echo return_data(true,"用户注册成功",$db);
}else{
    echo return_data(false,"不支持此请求","");
}
