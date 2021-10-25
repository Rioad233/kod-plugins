(function (){
    var version = "1.1";
    var ready = function (callback){
        if(window.$ && document.readyState == "complete"){
            callback();
        }else{
            setTimeout(ready,300,callback);
        }
    }
    ready(function (){
        var flag = confirm("检测到gitee有更新的版本,是否跳转?");
        if(!flag){
            var flag2 = confirm("下次是否继续提醒?");
            if(flag2){

            }
            return;
        }
    });
})()
