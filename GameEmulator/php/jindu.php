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
if($_GET["type"] == "1"){
    //获取用户当前游戏进度列表 {openId:"",game:""}
    $list = array();
    $tmp = userJinDuListGet($data["user_id"],$data["game"]);
    if(!$tmp){
        foreach($tmp as $item){
            array_push($list,$item);
        }
    }
    echo return_data(true,"进度读取成功",$list);
}else if($_GET["type"] == "2"){
    //获取加载数据 {fileId:""}
    $jdtext = userJinDuTextGet($data["fileId"]);
    echo return_data(true,"进度下载成功",$jdtext);
}else if($_GET["type"] == "3"){
     //保存用户的进度 {user_id:"oyPhY1c8x3hCHfUc1OXyjyvijlgs",game:"111111",name:"",text:"","fileId":""}
    userJinDuTextSet($data["fileId"],$data["text"]);
    $jindu = userJinDuListGet($data["user_id"],$data['game']);
    if(!$jindu){
        $jindu = array();
    }
    $id = md5(time()."-".rand(1000,9999));
    $jindu[$id] = array(
        "id"=>$id,
        "user_id"=>$data["user_id"],
        "game"=>$data["game"],
        "name"=>$data["name"],
        "file_id"=>$data["fileId"],
    );
    userJinDuListSet($data["user_id"],$data['game'],$jindu);
     echo return_data(true,"进度保存成功","");
}else if($_GET["type"] == "4"){
     //进度删除 {id:"",fileId:""}
    $item = userJinDuOneGet($data["id"]);
    userJinDuTextDel($item["file_id"]);
    $tmp = userJinDuListGet($item["user_id"],$item['game']);
    $jindu = array();
    foreach($tmp as $item){
        if($item['id']==$data["id"]){
            continue;
        }
        $jindu[$item["id"]] = $item;
    }
    userJinDuListSet($item["user_id"],$item['game'],$jindu);
     echo return_data(true,"进度删除成功","");
 }else{
    echo return_data(false,"不支持此请求","");
}
