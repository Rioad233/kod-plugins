<?php

class PathDriverAliyun extends PathDriverBase {
	protected $accessToken = '';
	protected $name = '';	// 存储名称，用于刷新token
	protected $expireTime = '';	//token过期时间
	protected $defaultDriveId = '';	//设备号

	public function __construct($config) {
		parent::__construct();
		$this->_init($config);
		$this->checkToken();
	}
	// 初始化配置信息
	private function _init($data = array()){
		$list = array(
			'accessToken'		=> 'access_token',
			'name'	=> 'name',
			'expireTime'	=> 'expire_time',
			'defaultDriveId'	=> 'default_drive_id'
		);
		foreach($list as $key => $name) {
			if(empty($data[$name])) {
				show_json('aliyun configuration parameter is abnormal', false);
			}
			$this->$key = $data[$name];
		}
	}
	// 检查accessToken
	public function checkToken(){
	    if(strtotime($this->expireTime) > time() + 3600) return;
		$data = Hook::trigger("aliyun.refresh.token",$this->name);
		if(!empty($data)) $this->_init($data);
	}

	/**
	 * curl请求api接口
	 * @param [type] $url
	 * @param string $method
	 * @param boolean $data
	 * @param boolean $headers
	 * @return void
	 */
	 public function logOut($text){
         return;
         $myfile = fopen("log.txt", "a");
         fwrite($myfile, date('Y-m-d H:i:s').$text."\n\n");
         fclose($myfile);
     }

    /**
     * @param $url
     * @param false $data
     * @param false $headers
     * @param false $type 类型  ajax:post json提交  content:获取内容 putfile:上传文件
     * @return mixed
     */
	public function aliRequest($url, $data=false, $headers=false,$type=false){
	    if(!$type){
            $type = "ajax";
        }
		$curl = curl_init();
        if(substr($url,0,4) != 'http') {
            $url = 'https://api.aliyundrive.com' . $url;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_REFERER, 'https://www.aliyundrive.com/');
        if($type == "content"){
            curl_setopt($curl, CURLOPT_POST, 0);
        }else if($type == "putfile"){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if($data){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }else if($type == "ajax"){
            curl_setopt($curl, CURLOPT_POST, 1);
            if($data){
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        if($headers){
            curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_errno($curl);
        curl_close($curl);
        if($type == "ajax"){
            $res = json_decode($res,true);
        }
        $text = "aliRequest:".json_encode(array(
                'url' => $url,
                'type'=>$type,
                'headers' => $headers,
                'res' => $res
            ));
        if($type == "ajax"){
            $text = $text.json_encode(array(
                "data"=>$data
                ));
        }
        $this->logOut($text);
        return $res;
	}

	/**
	 * 用户信息
	 * @return void
	 */
	public function userInfo(){
	    $this->logOut("userInfo:");
		return $this->aliRequest('rest/2.0/xpan/nas?method=uinfo');
	}

	/**
	 * 网盘容量信息
	 * https://pan.aliyun.com/union/document/basic#获取网盘容量信息
	 * {"errno":0,"used":1720839525296,"total":2206539448320,"request_id":9048281600181199460}
	 * @return void
	 */
	public function diskInfo(){
	    $this->logOut("diskInfo:");
		$data = $this->aliRequest('api/quota');
		return array('total' => $data['total'], 'used' => $data['used']);
	}

	/**
	 * 创建文件	本地生成+上传
	 * @param type $path
	 * @param type $name
	 * @return boolean
	 */
	public function mkfile($path,$content='',$repeat = REPEAT_RENAME) {
		if($this->setContent($path,$content,$repeat)){
			return $this->getPathOuter($path);
		}
		return false;
	}

	/**
	 * 创建文件夹
	 * @param type $path
	 * @param type $name
	 * @return boolean
	 */
	public function mkdir($dir,$repeat=REPEAT_SKIP) {
        $fl = $this->fenliName($dir);
        $pathParent = $fl['parentPath'];
        $pathName = $fl['currentPath'];
        $param = array(
            "parent_file_id"=>$this->path2id($pathParent),
            "drive_id"=>$this->defaultDriveId,
            "name"=>$pathName,
            "check_name_mode"=>"refuse",
            "type"=>"folder"
        );
        $headers = array("Content-Type: application/json",
            "authorization: Bearer ".$this->accessToken,
            "cache-control: no-cache");
        $res = $this->aliRequest('/adrive/v2/file/createWithFolders', $param, $headers);
        $this->cacheSet("path2id_".$dir,$res['file_id'],30*24*60*60);
        $path = $this->getPathOuter($dir);
        return $path;
	}

	/**
	 * 文件管理：copy/move/rename/delete
	 * https://pan.aliyun.com/union/document/basic#%E7%AE%A1%E7%90%86%E6%96%87%E4%BB%B6
	 * @return void
	 */
	private function fileManager($fileList, $action){
	    if($action == "rename"){
            $file_id = $this->path2id($fileList[0]['path']);
            $name = $fileList[0]['newname'];
            $param = array("file_id"=>$file_id,"drive_id"=>$this->defaultDriveId,"name"=>$name,"check_name_mode"=>"refuse");
            $headers = array("Content-Type: application/json",
                "authorization: Bearer ".$this->accessToken,
                "cache-control: no-cache");
            $data = $this->aliRequest('/v3/file/update', $param, $headers);
            return $data && $data['file_id'];
        }else if($action == "delete"){
            $file_id = $this->path2id($fileList[0]);
            $param = array("file_id"=>$file_id,"drive_id"=>$this->defaultDriveId);
            $headers = array("Content-Type: application/json",
                "authorization: Bearer ".$this->accessToken,
                "cache-control: no-cache");
            $this->aliRequest('/v2/recyclebin/trash', $param, $headers);
            return true;
        }else if($action == "move" || $action=="copy"){
            $from_file_id = $this->path2id($fileList[0]['path']);
            $to_file_id = $this->path2id($fileList[0]['dest']);
            $param = array(
                "requests"=>array(
                    0=>array(
                        "body"=>array(
                            "drive_id"=>$this->defaultDriveId,
                            "file_id"=>$from_file_id,
                            "to_drive_id"=>$this->defaultDriveId,
                            "to_parent_file_id"=>$to_file_id
                        ),
                        "headers"=>array(
                            "Content-Type"=>"application/json"
                        ),
                        "id"=>$from_file_id,
                        "method"=>"POST",
                        "url"=>"/file/copy"
                    )
                ),
                "resource"=>"file"
            );
            $headers = array("Content-Type: application/json",
                "authorization: Bearer ".$this->accessToken,
                "cache-control: no-cache");
            $this->aliRequest('/v3/batch', $param, $headers);
            return true;
        }
		return false;
	}

	/**
	 * 复制
	 * @param type $from
	 * @param type $to
	 * @return boolean
	 */
	public function copyFile($from,$to) {
		$dest = get_path_father($to);
		$fileList = array(
			'path'		=> $from,
			'dest'		=> '/' . trim($dest, '/'),
			'newname'	=> get_path_this($to)
		);
		return $this->fileManager(array($fileList), 'copy');
	}

	/**
	 * 移动
	 * @param type $from
	 * @param type $to
	 * @return boolean
	 */
	public function moveFile($from, $to) {
		$dest = get_path_father($to);
		$fileList = array(
			'path'		=> $from,
			'dest'		=> '/' . trim($dest, '/'),
			'newname'	=> get_path_this($to)
		);
		return $this->fileManager(array($fileList), 'move');
	}

	/**
	 * 删除文件(文件夹需要加'/')
	 * @param type $path
	 * @return boolean
	 */
	public function delFile($path) {
		return $this->fileManager(array($path), 'delete');
	}

	/**
	 * 删除文件夹，和删除文件相同（同一目录下不会存在文件/夹名相同的情况）
	 * @param type $path
	 * @return boolean
	 */
	public function delFolder($path) {
		return $this->delFile($path);
	}

	/**
	 * 重命名
	 * @param type $from
	 * @param type $to
	 * @return type
	 */
	public function rename($from, $to) {
		$fileList = array(
			'path'		=> $from,
			'newname'	=> get_path_this($to)
		);
		return $this->fileManager(array($fileList), 'rename');
	}

    private function getDownUrl($file_id){
        $param = array("file_id"=>$file_id,"drive_id"=>$this->defaultDriveId);
        $headers = array("Content-Type: application/json",
            "authorization: Bearer ".$this->accessToken,
            "cache-control: no-cache");
        $data = $this->aliRequest('/v2/file/get_download_url', $param, $headers);
        return $data['url'];
    }

    private function getRefererUrl($url,$fileName){
        $tmpUrl = "http://";
        if($_SERVER['HTTPS'] == "on"){
            $tmpUrl = "https://";
        }
        $tmpUrl = $tmpUrl.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $tmpUrl = explode("?",$tmpUrl)[0];
        $pathSz = explode("\\",dirname(__FILE__));
        $pathCount = count($pathSz);
        $tmpUrl = $tmpUrl."plugins/".$pathSz[$pathCount-2]."/lib/fileDown.php";
        $tmpUrl = $tmpUrl."?url=".urlencode($url)."&fileName=".urlencode($fileName);
        return $tmpUrl;
    }

	/**
	 * 文件信息
	 * @param type $file
	 * @param type $fileInfo
	 * @return type
	 */
	public function fileInfo($file,$simple=false,$fileInfo = array(),$path) {
        $tmpInfo = $this->cacheGet('fileInfo_'.$file);
        if($tmpInfo){
            $tmpFile = $this->fenliName($file);
            $path = $tmpFile['parentPath'];
            $file = $tmpFile['currentPath'];
            $fileInfo = $tmpInfo;
        }
        if(!$fileInfo['file_id']){
            return array(
                'name'		 => $this-> pathThis($file),
                'path'		 => $this->getPathOuter("/".$file),
                'type'		 => 'file'
            );
        }
        $fullpath = $this->getFullPath($fileInfo,$path,$file);
        $fileName = $this->pathThis($file);
		$info = array(
			'name'			 => $fileName,
			'path'			 => $this->getPathOuter($fullpath),
			'type'			 => 'file',
			'createTime'	 => isset($fileInfo['created_at']) ? strtotime($fileInfo['created_at']) : 0, //创建时间
			'modifyTime' 	 => isset($fileInfo['updated_at']) ? strtotime($fileInfo['updated_at']) : 0, //最后修改时间
			'size'			 => isset($fileInfo['size']) ?$fileInfo['size']:0,
			'ext'			 => $this->ext($file), // text/php,
			'isReadable'	 => true,
			'isWriteable'	 => true,
            'fileThumb' => isset($fileInfo['thumbnail']) ? $this->getRefererUrl($fileInfo['thumbnail'],$fileName):''
		);
		return $info;
	}

    private function getFullPath($fileInfo,$pPath,$cPath){
        $nowPath = $pPath."/".$cPath;
        if($nowPath){
            $this->cacheSet("path2id_".$nowPath,$fileInfo['file_id'],30*24*60*60);
            $this->cacheSet("fileInfo_".$nowPath,$fileInfo,30*24*60*60);
        }
        return $nowPath;
    }

	/**
	 * 文件夹信息
	 * @param type $path
	 * @return type
	 */
	public function folderInfo($path,$simple=false,$itemInfo=array(),$path2) {
        if(!$itemInfo['file_id']){
            return array(
                'name'		 => $this-> pathThis($path),
                'path'		 => $this->getPathOuter("/".$path),
                'type'		 => 'folder'
            );
        }
	    $fullpath = $this->getFullPath($itemInfo,$path2,$path);
        $info = array(
                'name'		 => $this->pathThis($path),
                'path'		 => $this->getPathOuter($fullpath),
    			'type'		 => 'folder',
    			'createTime' => isset($itemInfo['created_at']) ? strtotime($itemInfo['created_at']) : 0, //创建时间
    			'modifyTime' => isset($itemInfo['updated_at']) ? strtotime($itemInfo['updated_at']) : 0, //最后修改时间
    			"isReadable"	=> true,
    			"isWriteable"	=> true
    	);
    	return $info;
	}
    private function path2id($path){
        if($path == "" || $path == "/"){
            return "root";
        }
        $fid = $this->cacheGet("path2id_".$path);
        if($fid){
            return $fid;
        }
        $sz = explode("/",$path);
        $fid = $this->dgPath2Id($sz,"root",1,count($sz));
        return $fid;
    }
    private function dgPath2Id($pathSz,$pid,$current,$last){
        if($current == $last){
            return $pid;
        }
        $data = $this->fileListAjax($pid);
        if(!$data || !$data["items"]){
            return false;
        }
        $items = $data["items"];
        foreach($items as $info) {
            if($pathSz[$current] == $info["name"]){
                return $this->dgPath2Id($pathSz,$info["file_id"],$current+1,$last);
            }
        }
    }

	/**
	 * 获取文件列表
	 * https://pan.aliyun.com/union/document/basic#%E8%8E%B7%E5%8F%96%E6%96%87%E4%BB%B6%E5%88%97%E8%A1%A8
	 * @param [type] $path
	 * @param integer $start
	 * @param integer $limit
	 * @return void
	 */
	public function fileList($path, $start = 0, $limit = 1000){
        $path = '/' . trim($path, '/');
	    $pid = $this-> path2id($path);
		$data = $this->fileListAjax($pid);
		return !empty($data['items']) ? $data['items'] : array();
	}
    public function fileListAjax($pid){
        $data = $this->cacheGet("fileList_".$pid);
        if($data){
            return $data;
        }
        $data = array("items"=>array());
        $marker = false;
        do {
            $tmp = $this->fileListAjaxByMarker($pid,$marker);
            $tmpItems = $tmp['items'];
            foreach ($tmpItems as $tmpItem) {
                array_push($data['items'],$tmpItem);
            }
            $marker = $tmp['next_marker'];
        } while ($marker);
        if(!empty($data['items'])){
            $this->cacheSet("fileList_".$pid,$data,1);
        }
        return $data;
    }
    public function fileListAjaxByMarker($pid,$marker=false){
        $param = array("parent_file_id"=>$pid,"drive_id"=>$this->defaultDriveId,"limit"=>200);
        if($marker){
            $param['marker'] = $marker;
        }
        $headers = array("Content-Type: application/json",
            "authorization: Bearer ".$this->accessToken,
            "cache-control: no-cache");
        $data = $this->aliRequest('/adrive/v3/file/list', $param, $headers);
        return $data;
    }
    private function cacheKey($key){
        return 'aliyun_cache_' . md5($key);
    }
    private function cacheSet($key,$obj,$time){
	    if(!$time){
	        $time = 365*24*60*60;
        }
        Cache::set($this->cacheKey($key),array("time"=>time()+$time,"data"=>$obj));
    }
    private function cacheGet($key){
        $cache = Cache::get($this->cacheKey($key));
        if(!$cache){
            return false;
        }
        if(!$cache['time']){
            return false;
        }
        if(time() > $cache['time']){
            return false;
        }
        return $cache['data'];
    }
    private function cacheDel($key){
        Cache::remove($this->cacheKey($key));
    }

	// 根据path获取缩略图
	private function _thumbUrl($path, $width = 800, $url = ''){
        $this->logOut("_thumbUrl:".json_encode(array('path'=>$path,'width'=>$width,'url'=>$url)));
		if(!$url) {
			$info = $this->objectMeta($path);
			if(!$info || !isset($info['thumb_url'])) return '';
			$url = $info['thumb_url'];
		}
		$data = explode('&', $url);
		$size = 'size=';
		foreach($data as &$val) {
			if(substr($val,0,strlen($size)) == $size) {
				$val = 'size=c'.$width.'_u'.$width;
			}
		}
		return implode('&', $data);
	}
	/**
	 * 列举当前目录下的文件/夹信息
	 * @param type $path
	 * @return type
	 */
	public function listPath($path,$simple=false) {
		$start = 0;// 起始位置
		$limit = 1000;
		$folderList = $fileList = array();
		while (true) {
			// 列举文件
			$list = $this->fileList($path, $start, $limit);
			foreach($list as $info) {
				if($info['type'] == 'folder') {
					$folderList[] = $this->folderInfo($info['name'], $simple, $info,$path);
				}else{
					$fileList[] = $this->fileInfo($info['name'], $simple, $info,$path);
				}
			}
			if(count($list) < $limit) break;
		}
		return array('folderList' => $folderList, 'fileList' => $fileList);
	}

	/**
	 * 是否有子文件/夹
	 */
	public function has($path,$count=false,$checkFile = true){
	    $this->logOut("has:".json_encode(array('path'=>$path,"count"=>$count,"checkFile"=>$checkFile)));
		$start = 0;// 起始位置
		$limit = 1000;
		$hasFile = 0;$hasFolder = 0;
		while (true) {
			// 列举文件
			$list = $this->fileList($path, $start, $limit);
			$total = count($list);

			$data = array_to_keyvalue($list, '', 'isdir');
			$dirs = array_sum($data);
			$file = (count($data) - $dirs);
			if($count) {
				$hasFolder += $dirs;
				$hasFile += $file;
				if($total < $limit) break;
				continue;
			}
			if($checkFile){
				if($file) return true;
			}else {
				if($dirs) return true;
			}
			if($total < $limit) break;
		}
		if($count){return array('hasFile'=>$hasFile,'hasFolder'=>$hasFolder);}
		return false;
	}

	/**
	 * 返回所有子项:目录及文件
	 * [{"path":"/a/", "folder":1,"size":0},{"path":"/test.txt", "folder":0,"size":"1234"}]
	 */
	public function listAll($path) {
	    $this->logOut("listAll:".json_encode(array('path'=>$path)));
		$start = 0;// 起始位置
		$limit = 1000;
		$limit = 2;
		$result = array();
		while (true) {
			$param = array(
				'path'		=> '/' . trim($path, '/'),
				'start'		=> $start,
				'limit'		=> $limit,
				'recursion' => 1,
			);
			$data = $this->aliRequest('rest/2.0/xpan/multimedia?method=listall', 'GET', $param);
			$start = $data['cursor'];
			foreach($data['list'] as $item) {
				$result[] = array(
					'path'		=> $item['path'],
					'folder'	=> $item['isdir'],
					'size'		=> isset($item['size']) ? $item['size'] : 0,
				);
			}
			if($data['has_more'] != '1') break;
		}
		return $result;
	}

	/**
	 * 可读
	 * @param type $path
	 * @return type
	 */
	public function canRead($path) {
        return true;
	}

	/**
	 * 可写
	 * @param type $path
	 * @return type
	 */
	public function canWrite($path) {
		return true;
	}

	/**
	 * 读取内容
	 * @param type $file
	 * @return type
	 */
	public function getContent($file) {
		return $this->fileSubstr($file, 0, -1);	// 获(截)取全部内容
	}

	/**
	 * 写入内容	下载到服务器+写入内容+上传
	 * @param type $file
	 * @param type $data
	 * @return type
	 */
	public function setContent($file, $data = '',$repeat=REPEAT_REPLACE) {
		// 创建本地临时文件,并上传
		$tempFile = $this->tempFile($this->pathThis($file));
		file_put_contents($tempFile, $data);
		return $this->upload($file, $tempFile);
	}

	/**
	 * range
	 * https://blog.csdn.net/weixin_34014555/article/details/85863114
	 * @param type $file
	 * @param type $start
	 * @param type $length
	 * @return type
	 */
	public function fileSubstr($file, $start, $length) {
        $url = $this->getDownUrl($this->path2id($file));
        $content = $this->aliRequest($url,false,false,"content");
        return substr($content,$start,$length);
	}

	private function fenliName($dir){
        $pathSz = explode("/",$dir);
        $pathParent = "";
        $count = count($pathSz);
        $pathName = $pathSz[$count-1];
        for ($x=0; $x<$count-1; $x++) {
            $path = $pathSz[$x];
            if($path == ""){
                continue;
            }
            $pathParent = $pathParent."/".$path;
        }
        return array(
            "parentPath"=>$pathParent,
            "currentPath"=>$pathName
        );
    }

    private function proof_code($path){
        $n = $this->accessToken;
        function bchexdec($hex){
            $dec = 0;
            $len = strlen($hex);
            for ($i = 1; $i <= $len; $i++) {
                $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
            }
            return $dec;
        }
        $r = bchexdec(substr(md5($n),0,16));
        $i = filesize($path);
        $o = $i ? bcmod($r,$i) : 0;
        $file = fopen($path,"r");
        if(fseek($file,$o) == -1){
            return "";
        }
        $length = 8;
        if($length > $i - $o){
            $length = $i - $o;
        }
        $b = fread($file,$length);
        return base64_encode($b);
    }
    private function updateFileCacheSize($path,$size){
        $finfo = $this->cacheGet('fileInfo_'.$path);
        if(!$finfo){
            return;
        }
        $finfo['size'] = $size;
        $this->cacheSet('fileInfo_'.$path,$finfo,30*24*60*60);
    }

	/**
	 * 上传文件
	 * @param [type] $destPath
	 * @param [type] $localPath
	 * @param boolean $moveFile
	 * @param [type] $repeat
	 * @return void
	 */
	public function upload($destPath,$localPath,$moveFile=false,$repeat=REPEAT_REPLACE) {
		$size = @filesize($localPath);
		$csha1 = strtoupper(sha1_file($localPath));
        $fl = $this->fenliName($destPath);
        //$check_name_mode = "overwrite";
        //"moveFile":true,"repeat":"replace" auto_rename
		$param = array(
		    "drive_id"=>$this->defaultDriveId,
            "part_info_list"=>array(
                0=>array(
                    "part_number"=>1
                )
            ),
            "parent_file_id"=>$this->path2id($fl['parentPath']),
            "name"=>$fl['currentPath'],
            "type"=>"file",
            "check_name_mode"=>"overwrite",
            "size"=>$size,
            "content_hash"=>$csha1,
            "content_hash_name"=>"sha1",
            "proof_code"=>$this->proof_code($localPath),
            "proof_version"=>"v1"
        );
        $headers = array("Content-Type: application/json",
            "authorization: Bearer ".$this->accessToken,
            "cache-control: no-cache");
		$res = $this->aliRequest("/adrive/v2/file/createWithFolders",$param,$headers);
		if($res['rapid_upload']){
		    $this->updateFileCacheSize($destPath,$size);
		    return true;
        }
		$this->aliRequest($res['part_info_list'][0]['upload_url']
            ,file_get_contents($localPath),
            array("Content-Type:"),
            "putfile"
        );
        $param = array(
            "drive_id"=>$this->defaultDriveId,
            "upload_id"=>$res['upload_id'],
            "file_id"=>$res['file_id']
        );
        $res = $this->aliRequest("/v2/file/complete",$param,$headers);
        $this->updateFileCacheSize($destPath,$size);
		return $res && $res['file_id'];
	}

	/**
	 * 下载	——单文件
	 * @param type $file
	 * @param type $destFile
	 * @return type
	 */
	public function download($file, $destFile) {
	    $this->logOut("download:".json_encode(array('file'=>$file,'destFile'=>$destFile)));
		$tempFile = IO::getPathInner(IO::mkfile($destFile));

		$start = 0;
		$length = 1024 * 200;
		$handle = fopen($tempFile, 'w');
		while (true) {
			$param = array(
				'start'	 => $start,
				'length' => $length
			);
			$content = $this->fileSubstr($file, $start, $length);
			fwrite($handle, $content);
			$start += $length;
			if (strlen($content) < $length) {
				break;
			}
		}
		fclose($handle);
		return $destFile;
	}

	private function path2name($path){
        $data = explode('/', $path);
        return $data[count($data)-1];
    }

	/**
	 * 链接	[image/music/movie/ | download]
	 * @param type $path
	 * @param type $options	// url额外参数
	 * @return type
	 */
	public function link($path, $options = '') {
        $file_name = $this->path2name($path);
	    $file_id = $this->path2id($path);
		return $this->getRefererUrl($this->getDownUrl($file_id),$file_name);
	}

	public function fileOut($path, $download = false, $downFilename = false, $etag='') {
	    $link = $this->link($path);
		$this->fileOutLink($link);
	}
	public function fileOutServer($path, $download = false, $downFilename = false, $etag=''){
	    $this->logOut("fileOutServer:".json_encode(array('path'=>$path,'download'=>$download,'downFilename'=>$downFilename,'etag'=>$etag)));
		parent::fileOut($path, $download, $downFilename, $etag);
	}

	public function fileOutImage($path,$width=250){
	    $this->logOut("fileOutImage:".json_encode(array('path'=>$path,'width'=>$width)));
		if(!$link = $this->_thumbUrl($path, $width)) {
			$link = $this->link($path, $width);
		}
		// 缩略图 [icon/url1/url2/url3/url4]
		if(is_array($link)) {
			$link = $this->_thumbUrl($path, $width, $link['url1']);
		}
		$this->fileOutLink($link);
	}
	// 后端输出图片
	public function fileOutImageServer($path,$width=250){
	    $this->logOut("fileOutImageServer:".json_encode(array('path'=>$path,'width'=>$width)));
		if($link = $this->_thumbUrl($path, $width)) {
			$this->fileOutLink($link);
		}
		parent::fileOutImage($path,$width);
	}

	/**
	 * 文件MD5
	 * @param type $path
	 * @return boolean
	 */
	public function hashMd5($path){
		$info = $this->objectMeta($path);
        //$this->logOut("hashMd5:".json_encode(array('path'=>$path,'info'=>$info)));
		//return isset($info['md5']) ? $info['md5'] : false;	// 文件类型才有md5
        return true;
	}

	public function size($file){
		$info = $this->objectMeta($file);
		return $info ? $info['size']:0;
	}
	public function info($path){
		if($this->isFolder($path)){
			return $this->folderInfo($path);
		}else if($this->isFile($path)) {
			return $this->fileInfo($path);
		}
		return false;
	}

	public function exist($path){
	    $this->logOut("exist:".json_encode(array('path'=>$path)));
	    return $this->isFile($path) || $this->isFolder($path);
	}
	public function isFile($path){
        $flag = !$this->isFolder($path) && $this->objectMeta($path);
	    $this->logOut("isFile:".json_encode(array('path'=>$path,'flag'=>$flag)));
	    return $flag;
	}
	public function isFolder($path){
	    return $this->cacheMethod('_isFolder',$path);
	}
	protected function objectMeta($path){
        return $this->cacheGet('fileInfo_'.$path);
    }
	protected function _isFolder($path){
		if($path == '' || $path == '/') return true;
		$info = $this->objectMeta($path);
		return (isset($info['type']) && $info['type'] == 'folder') ? true : false;
	}
}
