(function (){
    function getCurrentScript() {
        var js = "update.js";
        var script = document.currentScript;
        if(!script && document.querySelector){
            script = document.querySelector("script[src*='"+js+"']");
        }
        if(!script){
            var scripts = document.getElementsByTagName("script");
            for (var i = 0, l = scripts.length; i < l; i++) {
                var src = scripts[i].src;
                if (src.indexOf(js) != -1) {
                    script = scripts[i];
                    break;
                }
            }
        }
        return script;
    }
    function getHost(src,length){
        var ss = src.split("/");
        ss.length = ss.length - length;
        var path = ss.join("/");
        return path;
    }
    function asyncLoadData(url,fn){
        var xmlhttp;
        if (window.XMLHttpRequest){
            xmlhttp=new XMLHttpRequest();
        }else{
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange=function(){
            if(xmlhttp.readyState==4){
                try{
                    fn(xmlhttp.responseText);
                }catch (e) {
                    console.warn(e);
                }
            }
        }
        xmlhttp.open("GET",url,true);
        xmlhttp.send();
    }
    function replaceAll(str,str1,str2){
        while(true){
            if(str.indexOf(str1)==-1){
                break;
            }
            str = str.replace(str1,str2);
        }
        return str;
    }
    var script = getCurrentScript();
    var host = getHost(script.src,2);
    var ready = function (callback){
        if(window.$ && document.readyState == "complete"){
            callback();
        }else{
            setTimeout(ready,300,callback);
        }
    }
    function initUpdate(){
        var time = new Date().getTime();
        asyncLoadData(host+"/package.json?_="+time,function(localJsonStr){
            localJsonStr = replaceAll(localJsonStr,"\n","");
            localJsonStr = replaceAll(localJsonStr," ","");
            localJsonStr = replaceAll(localJsonStr,"\t","");
            var localVersion = JSON.parse(localJsonStr).version;
            asyncLoadData(host+"/lib/getVersion.php?_="+time,function(remoteJsonStr){
                remoteJsonStr = replaceAll(remoteJsonStr,"\n","");
                remoteJsonStr = replaceAll(remoteJsonStr," ","");
                remoteJsonStr = replaceAll(remoteJsonStr,"\t","");
                var remoteVersion = JSON.parse(remoteJsonStr).version;
                if(localVersion == remoteVersion){
                    return;
                }
                var tx = localStorage.getItem("version_aliyunDrive_"+remoteVersion);
                if(tx == "false"){
                    return;
                }
                var flag = confirm("?????????????????????????????????????????????"+remoteVersion+",???????????????????");
                if(flag){
                    var a = document.createElement("a");
                    a.target = "_blank";
                    a.href = "https://gitee.com/fs185085781/kod-plugins/tree/master";
                    a.click();
                    return;
                }
                var flag2 = confirm("??????????????????????????????????(??????:??????????????????,??????:??????????????????)");
                if(!flag2){
                    return;
                }
                localStorage.setItem("version_aliyunDrive_"+remoteVersion,"false");
            });
        });
    }
    ready(function (){
        asyncLoadData(window.location.origin+window.location.pathname+"?user/view/options",
            function(res){
                if(JSON.parse(res).data.user.isRoot){
                    initUpdate();
                }
            });
    });
})()
