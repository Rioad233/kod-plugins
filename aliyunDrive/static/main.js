kodReady.push(function(){
	var staticPath = "{{pluginHost}}static/";
	// var version = '?v={{package.version}}';

	var aliyunPkg = {};
	Events.bind('storage.init.load', function(self){
		requireAsync(staticPath+'package.js', function(package){
			aliyunPkg = package;
		});
		// 添加菜单
		var key = 'aliyun';
		if(_.isUndefined(self.typeList[key])) {
			self.typeList[key] = '阿里云盘';
			self.iconList[key] = '<i class="path-ico name-kod-aliyun"><img src="{{pluginHost}}static/images/icon.png"></i>';
		}
	});
	// 存储form赋值
	Events.bind('storage.config.form.load', function(type, formData){
		if(type != 'aliyun') return;
		_.extend(formData, $.objClone(aliyunPkg));
	});
	// 屏蔽设为默认
	Events.bind('storage.list.view.load', function(self){
		var storeList = self.parent.storeListAll || {};
		_.each(storeList, function(item){
			if(_.toLower(item.driver) == 'aliyun') {
				self.$(".app-list [data-id='"+item.id+"'] .dropdown-menu li").eq(0).hide();
			}
		});
	});

	if($.hasKey('plugin.aliyunDrive.style')) return;
	requireAsync("{{pluginHost}}static/main.css");
});