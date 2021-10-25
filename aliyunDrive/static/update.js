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
    var script = getCurrentScript();
    var host = getHost(script.src,2);
    var ready = function (callback){
        if(window.$ && document.readyState == "complete"){
            callback();
        }else{
            setTimeout(ready,300,callback);
        }
    }
    ready(function (){
        asyncLoadData(host+"/package.json",function(localJsonStr){
            var localVersion = JSON.parse(localJsonStr).version;
            asyncLoadData(host+"/lib/getVersion.php",function(remoteJsonStr){
                var remoteVersion = JSON.parse(remoteJsonStr).version;
                console.log("version",localVersion,remoteVersion);
                if(localVersion == remoteVersion){
                    return;
                }
            });
        });
    });
})()
