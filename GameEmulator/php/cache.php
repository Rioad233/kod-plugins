<?php
function return_data($flag,$msg,$data){
    $res = array("flag"=>$flag,"msg"=>$msg);
    if(!empty($data)){
        $res["data"] = $data;
    }
    $str = json_encode($res);
    return $str;
}
function cacheKey($path,$key){
    return 'db/game_cache_'.$path. md5($key);
}
function autoMkdir(){
    if(!is_dir("db")){
        mkdir("db",0777,true);
    }
}
function cacheSet($path,$key,$obj){
    autoMkdir();
    file_put_contents(cacheKey($path,$key),json_encode($obj));
}
function cacheGet($path,$key){
    $data = file_get_contents(cacheKey($path,$key));
    if(!$data){
        return false;
    }
    return json_decode($data,true);
}
function cacheDel($path,$key){
    unlink(cacheKey($path,$key));
}
function userInsertUpdate($user){
    if($user['id']){
        cacheSet("user_id_",$user["id"],$user);
    }
    if($user['username']){
        cacheSet("user_username_",$user["username"],$user);
    }
}
function userDelete($user){
    if($user['id']){
        cacheDel("user_id_",$user["id"]);
    }
    if($user['username']){
        cacheDel("user_username_",$user["username"]);
    }
}
function userSelect($data){
    $user=false;
    if(!empty($data["id"])){
        if(!$user){
            $user = userSelectById($data["id"]);
        }
    }
    if(!empty($data["username"]) && !empty($data["password"])){
        if(!$user){
            $user = userSelectByUserName($data["username"]);
            if($user && $user["password"] != $data["password"]){
                $user = false;
            }
        }
    }
    return $user;
}
function userSelectById($id){
    return cacheGet("user_id_",$id);
}
function userSelectByUserName($username){
    return cacheGet("user_username_",$username);
}
function userJinDuListGet($userId,$game){
    return cacheGet("user_jindu_".$userId."_",$game);
}
function userJinDuListSet($userId,$game,$jindu){
    cacheSet("user_jindu_".$userId."_",$game,$jindu);
}
function userJinDuTextGet($fileId){
    return file_get_contents(cacheKey("user_jindu_file_",$fileId));
}
function userJinDuTextSet($fileId,$txt){
    autoMkdir();
    file_put_contents(cacheKey("user_jindu_file_",$fileId),$txt);
}
function userJinDuTextDel($fileId){
    cacheDel("user_jindu_file_",$fileId);
}
function userJinDuOneSet($item){
    cacheSet("user_jindu_item_",$item['id'],$item);
}
function userJinDuOneGet($id){
    return cacheGet("user_jindu_item_",$id);
}
function setGameCookie($cookie){
    cacheSet("cookie_","1",$cookie);
}
function getGameCookie(){
    return cacheGet("cookie_","1");
}
