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
    //登录 {id:"",wx_open_id:"",qq_open_id:"",username:"",password:""}
    if(empty($data["id"]) && empty($data["wx_open_id"]) && empty($data["qq_open_id"]) && (empty($data["username"]) || empty($data["password"]))){
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
}else if($_GET["type"] == "bind"){
    //绑定{id:"",wx_open_id:"",qq_open_id:""}
    if(empty($data["id"])){
        echo return_data(false,"id不可为空","");
        return;
    }
    if(empty($data["wx_open_id"]) && empty($data["qq_open_id"])){
        echo return_data(false,"绑定数据不可为空","");
        return;
    }
    $update = userSelectById($data['id']);
    if(!empty($data["wx_open_id"])){
        $update["wx_open_id"] = $data["wx_open_id"];
        $wx = userSelectByWx($data["wx_open_id"]);
        if(!empty($wx) && $wx["id"] != $data["id"]){
            //将$wx删掉,并且$wx对应的进度归属给$data["id"]
            userDelete($wx);
            //$database->update("nes_user_jindu",array("user_id"=>$data["id"]),array("user_id"=>$wx["id"]));
        }
    }
    if(!empty($data["qq_open_id"])){
        $update["qq_open_id"] = $data["qq_open_id"];
        $qq = userSelectByQQ($data["qq_open_id"]);
        if(!empty($qq) && $qq["id"] != $data["id"]){
            //将$qq删掉,并且$qq对应的进度归属给$data["id"]
            userDelete($qq);
            //$database->update("nes_user_jindu",array("user_id"=>$data["id"]),array("user_id"=>$qq["id"]));
        }
    }
    userInsertUpdate($update);
    $db = userSelectById($data['id']);
    $db["password"] = "";
    echo return_data(true,"绑定成功",$db);
}else if($_GET["type"] == "config"){
    //保存配置{id:"",config:""}
    if(empty($data["id"])){
        echo return_data(false,"id不可为空","");
        return;
    }
    if(empty($data["config"])){
        echo return_data(false,"配置不可为空","");
        return;
    }
    $update = userSelectById($data["id"]);
    $update['config'] = $data["config"];
    userInsertUpdate($update);
    echo return_data(true,"保存配置成功","");
}else{
    echo return_data(false,"不支持此请求","");
}
