//var nesromurl="";
kodReady.push(function(){
	Events.bind('explorer.kodApp.before',function(appList){
		appList.push({
			name:"GameEmulator",
			title:LNG['admin.plugin.defaultGameEmulator'],
			icon:'{{pluginHost}}static/image/icon.png',
			ext:"{{config.fileExt}}",
			sort:"{{config.fileSort}}",
			callback:function(path,ext,name){
				var url = '{{pluginApi}}&path='+ encodeURIComponent(path)+'&ext='+encodeURIComponent(ext)+'&name='+encodeURIComponent(name);
					if('window' == "{{config.openWith}}" && !core.isFileView()){
						window.open(url);
					}else{
						core.openDialog(url,core.icon('{{pluginHost}}static/image/icon.png'),"GameEmulator:"+name);
					}
			},
		});
	});
	if(!$.hasKey('plugin.GameEmulator.style')){
		//只有首次处理,避免重复调用
		var fextSz = "{{config.fileExt}}".split(",");
		for(var i=0;i<fextSz.length;i++){
			var fext = fextSz[i];
			var length = fext.length;
			var size = Math.pow(2,length);
			for(var n=0;n<size;n++){
				var set = n.toString(2);
				while(true){
					if(set.length == length){
						break;
					}
					set = "0"+set;
				}
				var fileName = "";
				for(var m=0;m<length;m++){
					if(parseInt(set[m])){
						fileName += fext[m].toUpperCase()
					}else{
						fileName += fext[m];
					}
				}
				$.addStyle(".x-item-icon.x-"+fileName+"{background-image:url('{{pluginHost}}static/image/fileicon.png');}");
			}
		}
		var time = new Date().getTime();
		if(window.$ && document.readyState == "complete"){
			var body = document.getElementsByTagName("body")[0];
			var script=document.createElement("script");
			script.src="{{pluginHost}}static/update.js?_="+time;
			body.insertBefore(script,body.firstChild);
		}else{
			document.write("<script src='{{pluginHost}}static/update.js?_="+time+"'></script>");
		}
	}
});
