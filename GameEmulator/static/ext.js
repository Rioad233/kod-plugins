(function (){
    var kodUserID = utils.getCookie("kodUserID");
    var host = window.location.host;
    utils.delayAction(function (){
        return utils.user && utils.$;
    },function (){
        var data = {"username":host+kodUserID,"password":"123456"};
        utils.$.post("/user.php?type=reg",data,function (res) {
            if(res.flag){
                utils.setLocalStorage("user",res.data);
                return;
            }
            utils.$.post("/user.php?type=login",data,function (res2) {
                if(res2.flag){
                    utils.setLocalStorage("user",res2.data);
                }
            },true);
        },true);
    });
})()
