define(function(require, exports) {
    return {
        "name":{
            "type":"input",
            "value":"",
            "display":LNG['common.name'],
            "desc":LNG['admin.storage.nameDesc'],
            "require":1
        },
        "access_token": {
            "type":"input",
            "value":"",
            "className": "hidden"
        },
        "expire_time": {
            "type":"input",
            "value":"",
            "className": "hidden",
        },
        "default_drive_id": {
            "type":"input",
            "value":"",
            "className": "hidden",
        },
        "refresh_token": {
            "type":"input",
            "value":"",
            "require":1,
            "display": "refresh_token"
        },
        "tishi": {
            "type":"input",
            "value":"javascript:void((function(){alert(\"refresh_token:\"+JSON.parse(localStorage.getItem(\"token\")).refresh_token)})())",
            "desc":"将此代码复制到阿里云盘地址栏'粘贴并访问'即可获取",
            "display": "提示"
        }
    };
});
