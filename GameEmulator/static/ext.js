(function (){
    if(window.location.protocol != "http:"){
        alert("当前暂不支持https访问,请使用http访问");
        return;
    }
    window.env = "thrid";
    function init(){
        var kodUserID = utils.getCookie("kodUserID");
        var host = window.location.host;
        window.utils.delayAction(function (){
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
    }
    var time = setInterval(function (){
        if(window.utils){
            init();
            clearInterval(time);
        }
    },300);
})()
