<?php
function return_data($flag,$msg,$data){
    $res = array("flag"=>$flag,"msg"=>$msg);
    if(!empty($data)){
        $res["data"] = $data;
    }
    $str = json_encode($res);
    return $str;
}
function cacheKey($key){
    return 'db/game_cache_' . md5($key);
}
function cacheSet($key,$obj){
    file_put_contents(cacheKey($key),json_encode($obj));
}
function cacheGet($key){
    $data = file_get_contents(cacheKey($key));
    if(!$data){
        return false;
    }
    return json_decode($data,true);
}
function cacheDel($key){
    unlink(cacheKey($key));
}
function userInsertUpdate($user){
    if($user['id']){
        cacheSet("user_id_".$user["id"],$user);
    }
    if($user['username']){
        cacheSet("user_username_".$user["username"],$user);
    }
    if($user['wx_open_id']){
        cacheSet("user_wx_open_id_".$user["wx_open_id"],$user);
    }
    if($user['qq_open_id']){
        cacheSet("user_qq_open_id_".$user["qq_open_id"],$user);
    }
}
function userDelete($user){
    if($user['id']){
        cacheDel("user_id_".$user["id"]);
    }
    if($user['username']){
        cacheDel("user_username_".$user["username"]);
    }
    if($user['wx_open_id']){
        cacheDel("user_wx_open_id_".$user["wx_open_id"]);
    }
    if($user['qq_open_id']){
        cacheDel("user_qq_open_id_".$user["qq_open_id"]);
    }
}
function userSelect($data){
    $user=false;
    if(!empty($data["wx_open_id"])){
        if(!$user){
            $user = userSelectByWx($data["wx_open_id"]);
        }
    }
    if(!empty($data["id"])){
        if(!$user){
            $user = userSelectById($data["id"]);
        }
    }
    if(!empty($data["qq_open_id"])){
        if(!$user){
            $user = userSelectByQQ($data["qq_open_id"]);
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
    return cacheGet("user_id_".$id);
}
function userSelectByUserName($username){
    return cacheGet("user_username_".$username);
}
function userSelectByQQ($qid){
    return cacheGet("user_qq_open_id_".$qid);
}
function userSelectByWx($wid){
    return cacheGet("user_wx_open_id_".$wid);
}
function userJinDuListGet($userId,$game){
    return cacheGet("user_jindu_".$userId."_".$game);
}
function userJinDuListSet($userId,$game,$jindu){
    cacheSet("user_jindu_".$userId."_".$game,$jindu);
}
function userJinDuTextGet($fileId){
    $key = "user_jindu_file_".$fileId;
    return file_get_contents(cacheKey($key));
}
function userJinDuTextSet($fileId,$txt){
    $key = "user_jindu_file_".$fileId;
    file_put_contents(cacheKey($key),$txt);
}
function userJinDuTextDel($fileId){
    cacheDel("user_jindu_file_".$fileId);
}
function userJinDuOneSet($item){
    cacheSet("user_jindu_item_".$item['id'],$item);
}
function userJinDuOneGet($id){
    return cacheGet("user_jindu_item_".$id);
}
