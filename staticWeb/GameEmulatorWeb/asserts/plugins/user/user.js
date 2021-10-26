(function () {
    utils.user = {
        isWeiXin:function(){
            var ua = navigator.userAgent.toLowerCase();
            var isWeixin = ua.indexOf('micromessenger') != -1;
            if (isWeixin) {
                return true;
            }else{
                return false;
            }
        },
        isQQ:function(){
            var ua = navigator.userAgent.toLowerCase();
            var isqq = ua.indexOf(' qq') != -1;
            if (isqq) {
                return true;
            }else{
                return false;
            }
        },
        isLogin:function(){
            return this.getUserInfo()!=null;
        },
        getUserInfo:function(){
            var user = utils.getLocalStorage("user");
            if(user){
                return user;
            }else{
                return null;
            }
        },
        getOpenId:function(){
            var that = this;
            if(utils.getParamer("wxOpenId")){
                return;
            }
            if(utils.getParamer("qqOpenId")){
                return;
            }
            if(that.isQQ() || that.isWeiXin()){
                window.location.href = utils.rootPath + "/openid.html?url="+encodeURIComponent(window.location.href);
            }
        },
        autoLogin:function() {
            var that = this;
            if(that.isLogin()){
                return;
            }
            function callback(){
                var left = window.location.origin + window.location.pathname;
                var right = window.location.hash;
                var count = 0;
                var search = "";
                var map = utils.getSearch();
                utils.removeProp(map,"userId")
                utils.removeProp(map,"wxOpenId")
                utils.removeProp(map,"qqOpenId")
                for(var key in map){
                    if(count == 0){
                        search += "?";
                    }else{
                        search += "&";
                    }
                    count++
                    search += key + "=" + map[key];
                }
                var newUrl = left + search + right;
                window.location.href = newUrl;
            }
            if(utils.getParamer("userId")){
                that.loginById(utils.getParamer("userId"),callback);
            }else if(utils.getParamer("wxOpenId") || utils.getParamer("qqOpenId")){
                that.thirdLogin(utils.getParamer("wxOpenId"),utils.getParamer("qqOpenId"),callback);
            }else if(that.isQQ() || that.isWeiXin()){
                that.getOpenId();
            }
        },
        thirdLogin:function (wxOpenId,qqOpenId,callback) {
            var wx_open_id = wxOpenId || "";
            var qq_open_id = qqOpenId || "";
            utils.$.post("/user.php?type=login",{wx_open_id:wx_open_id,qq_open_id:qq_open_id},function (res) {
                if(res.flag){
                    utils.setLocalStorage("user",res.data);
                    if(callback){
                        callback();
                    }
                }
            },true);
        },
        loginByUsername:function(data){
            utils.$.post("/user.php?type=login",data,function (res) {
                utils.setLocalStorage("user",res.data); //密码登录
                window.location.reload();
            });
        },
        regUser:function(data){
            utils.$.post("/user.php?type=reg",data,function (res) {
                utils.setLocalStorage("user",res.data); //用户注册
                window.location.reload();
            });
        },
        loginOut:function () {
            utils.delLocalStorage("user");
            return true;
        },
        toBind:function () {
            if(!this.isLogin()){
                return;
            }
            window.location.href = utils.rootPath+"/bind.html";
        },
        updateUser:function (user) {
            utils.setLocalStorage("user",user); //绑定后更新
        },
        loginById:function(id,callback){
            utils.$.post("/user.php?type=login",{id:id},function (res) {
                utils.setLocalStorage("user",res.data); //id自动登录
                if(callback){
                    callback();
                }
            });
        },
    };
    utils.delayAction(function () {
        return document.readyState == "complete";
    },function () {
        if(window.location.protocol == "https:"){
            utils.$.get("/user.php",function(res){
                if(res.msg.indexOf("Network Error") != -1){
                    window.location.href = "http:"+window.location.href.substring(6);
                }
            },true);
        }
        utils.user.autoLogin();
    });

})()
