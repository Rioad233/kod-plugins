<?php

/**
 * 阿里云盘存储对接
 * https://www.aliyundrive.com
 */
class aliyunDrivePlugin extends PluginBase{
	function __construct(){
		parent::__construct();
	}

	public function regist(){
		$this->hookRegist(array(
			'globalRequest'					=> 'aliyunDrivePlugin.autoRun',
			'user.commonJs.insert'			=> 'aliyunDrivePlugin.echoJs',
			'admin.plugin.setconfig.before'	=> 'aliyunDrivePlugin.appSetBefore',
			'admin.storage.add.before'		=> 'aliyunDrivePlugin.storeBefore',
			'admin.storage.edit.before'		=> 'aliyunDrivePlugin.storeBefore',
			'aliyun.refresh.token'			=> 'aliyunDrivePlugin.refreshToken',
		));
	}
	public function autoRun(){
		include_once($this->pluginPath.'lib/PathDriverAliyun.class.php');
	}
	public function echoJs(){
		$this->echoFile('static/main.js');
	}
	public function logOut($text){
	    return;
        $myfile = fopen("log.txt", "a");
        fwrite($myfile, date('Y-m-d H:i:s').$text."\n\n");
        fclose($myfile);
	}

	// 切换插件状态
	public function onChangeStatus($status){
		if(!_get($GLOBALS,'isRoot')) show_json(LNG('explorer.noPermissionAction'),false);
		if($status != '1') return;
	}
	public function appSetBefore(){
		if($this->in['app'] != $this->pluginName) return;
		if(!_get($GLOBALS,'isRoot')) show_json(LNG('explorer.noPermissionAction'),false);
	}

	/**
	 * 存储新增/编辑前，数据处理
	 * @return void
	 */
	public function storeBefore(){
		$driver = Input::get('driver');
		if(!$driver || strtolower($driver) != 'aliyun') return;

		$data = Input::getArray(array(
			"id"		=> array("default"=>null),
			"name" 		=> array("check"=>"require","default"=>null),
			"driver" 	=> array("check"=>"require","default"=>null),
			"editForce"	=> array("default"=>0),
			"config" 	=> array("check"=>"require","default"=>null),
		));
		$config = json_decode($data['config'], true);
		if(!$config['expire_time'] || !$config['access_token'] || !$config['default_drive_id']){
		    $tmp = $this->getTokenNew($config['refresh_token']);
		    if($tmp['flag']){
                $config['expire_time'] = $tmp['expire_time'];
                $config['access_token'] = $tmp['access_token'];
                $config['refresh_token'] = $tmp['refresh_token'];
                $config['default_drive_id'] = $tmp['default_drive_id'];
            }
		}
		$valids = array('expire_time','access_token','refresh_token','default_drive_id');
		foreach($valids as $name) {
			if(empty($config[$name])) show_json('refresh_token设置有误，请尝试重新设置', false);
		}
		$config['name'] = $data['name'];

		// 新增
		if(!$data['id']) return $this->addStore($data, $config);
		// 编辑
		$this->in['editForce'] = 0;	// 不再检查连接
		$this->in['config'] = json_encode($config);
	}

	// 新增存储
	private function addStore($data, $config){
		$list = Model('Storage')->listData();
		$list = array_to_keyvalue($list, 'name');
		if (isset($list[$data['name']])) {
			show_json('名称已存在', false);
		}
		$data['config'] = json_encode($config);
		$res = Model('Storage')->insert($data);
		$msg = $res ? LNG('explorer.success') : LNG('explorer.error');
		show_json($msg,!!$res);
	}

	/**
	 * 刷新accessToken
	 * @param [type] $name
	 * @return void
	 */
	public function refreshToken($name){
		$model = Model('Storage');
		$store = $model->findByName($name);
		if(!$store) return;
		$config = $model->getConfig($store['id']);
		$tmp = $this-> getTokenNew($config['refresh_token']);
		$this->logOut("refreshToken:".json_encode(array("old"=>$config,"new"=>$tmp)));
		if($tmp['flag']){
		    $config['expire_time'] = $tmp['expire_time'];
		    $config['access_token'] = $tmp['access_token'];
		    $config['refresh_token'] = $tmp['refresh_token'];
		    $config['default_drive_id'] = $tmp['default_drive_id'];
		}
		// 更新存储
		$store['config'] = json_encode($config);
		$model->update($store['id'], $store);
	}
	public function getTokenNew($refresh_token){
	    $res = $this->ajax(
	    'https://api.aliyundrive.com/token/refresh',
	    array('refresh_token'=>$refresh_token),
	    array(
	    "Content-Type: application/json"
	    ));
	    $this->logOut("getTokenNew:".json_encode(array("refresh_token"=>$refresh_token,"res"=>$res)));
	    if(!$res['flag']){
        	return;
        }
        if($res['data'] && $res['data']['access_token']){
            return array(
            'access_token'=>$res['data']['access_token'],
            'refresh_token'=>$res['data']['refresh_token'],
            'expire_time'=>$res['data']['expire_time'],
            'default_drive_id'=>$res['data']['default_drive_id'],
            'flag'=>true
            );
        }
	    return array();
	}
	public function ajax($url,$data=false,$headers=false){
	    $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if($data){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if($headers){
            curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        curl_close($curl);
        if($errorno){
            return array('flag'=>false,'msg'=>$errorno);
        }else{
            return array('flag'=>true,'data'=>json_decode($res,true));
        }
	}
}
