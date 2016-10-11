<?php namespace Goodspb\LaravelEasemob;

use Config , Storage ;

class easemob
{
    //Laravel DI
    public $app;

    //错误代码
    static $error_code = array(
        'invalid_grant' => '用户名或者密码输入错误',
        'organization_application_not_found' => '找不到aachatdemoui对应的app, 可能是URL写错了',
        'illegal_argument' => array(
                                'username'  => '创建用户请求体未提供”username”',
                                'password'  => '创建用户请求体未提供”password”',
                                'newpassword'   => '修改用户密码的请求体没提供newpassword属性',
                                'username1'     => '批量添加群组时预加入群组的新成员username不存在',
                            ),
        'json_parse'    => '发送请求时请求体不符合标准的JSON格式,服务器无法正确解析',
        'duplicate_unique_property_exists'  => '用户名已存在',
        'unauthorized'  => 'app的用户注册模式为授权注册,但是注册用户时请求头没带token',
        'auth_bad_access_token'  => 'token过期',
        'service_resource_not_found'  => 'URL指定的资源不存在',
        'Request'  => '请求体过大，比如超过了5Kb，拆成几个更小的请求体重试即可',
        'no_full_text_index'  => 'username不支持全文索引,不可以对该字段进行contains操作',
        'unsupported_service_operation'  => '请求方式不被发送请求的URL支持',
        'web_application'  => '错误的请求, 给一个未提供的API发送了请求',
    );
    //配置文件
    static  $easemob_config = null;
    //使用josn保存$token
    static  $token = null;
    //错误信息
    static $errmsg = '';

    function __construct($app){
        $this->app = $app;
        define('TIMESTAMP',time());
    }

    function init_config(){
        if(self::$easemob_config==null){
            $config = Config::get('easemob');
            $config['APPKEY'] = $config['ORG_NAME']."#".$config['APP_NAME'];
            self::$easemob_config = $config;
        }
        return self::$easemob_config;
    }

    function get_config(){
        if(self::$easemob_config!=null){
            return self::$easemob_config;
        }
        return $this->init_config();
    }

    /**
     * API请求
     * @param $path
     * @param $request_body
     * @param string $http_method
     * @param array $request_header
     * @param bool $force_request_body_array  知否强制 $request_body为array
     * @return array
     */
	function api_request($path , $request_body , $http_method ='POST', $request_header = array('Content-Type: application/json') , $param_use_array = false) {
		if (!function_exists('curl_init')) {
			return array("0", "无法使用curl");
		}

        $config = $this->get_config();

		$url = "http://" . $config['EASEMOB_DOMAIN'] . "/" . $config['ORG_NAME'] . "/" . $config['APP_NAME'] ."/". $path;

		$host = $config['EASEMOB_DOMAIN'];
		$port = "80";
		$timeout = 20;

        //处理参数
        $param = '';
        if(is_array($request_body) && $param_use_array == false){
            foreach($request_body as $key=>$val){
                $param .= ($param == '' ? '' : '&') . $key .'=' . $val;
            }
        }else{
            $param = $request_body;
        }

		$ch = curl_init();
		$header_array = array("Host: " . $host);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($header_array,$request_header));
        $http_method = strtoupper($http_method);
		if ($http_method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		}elseif($http_method == 'GET') {
			$url .= "?" . $param;
		}elseif($http_method == 'DELETE'){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		$status = curl_getinfo($ch);
		$errno = curl_errno($ch);
		curl_close($ch);

		return array($status['http_code'], $data);
	}

    //获取token，当操作有权限的行为时，需要token
    private function get_token(){

        if(self::$token==null){
            $config = $this->get_config();
            $file = $config['TOKEN_PATH'];
            if( Storage::disk()->exists($file) && TIMESTAMP - ( $createTime = Storage::disk()->lastModified($file) ) <= 7200 ){
                $token = Storage::disk()->get($file);
                self::$token = json_encode(array(
                    'token'=>$token,
                    'createtime'=>$createTime
                ));
                return $token;
            }
        }else{
            $token = json_decode(self::$token,true);
            if($token['createtime'] + 7200 > TIMESTAMP){
                return $token['token'];
            }
        }

        //返回远程token
        return $this->get_remote_token();
    }

    //从接口中获得token
    private function get_remote_token(){
        $config = $this->get_config();
        $file = $config['TOKEN_PATH'];
        $request_body = array('grant_type'=>'client_credentials','client_id'=>$config['CLIENT_ID'],'client_secret'=>$config['CLIENT_SECRET']);
        list($status_code, $reponse_body) = $this->api_request('token', json_encode($request_body) , 'POST');
        if ($status_code == "200") {
            $reponse = json_decode($reponse_body,true);
            $extend_update = htmlspecialchars(serialize(array('expires_in'=> $reponse['expires_in'],'createtime'=>TIMESTAMP ,'application'=>$reponse['application'])));
            if(Storage::disk()->exists($file)){
                Storage::disk()->delete($file);
            }
            Storage::disk()->put($file, $reponse['access_token'] );
            self::$token = json_encode(array(
                'token' => $reponse['access_token'],
                'createtime'=> TIMESTAMP
            ));
            return $reponse['access_token'];
        } else {
            throw new \Exception("获取token失败，请联系管理员");
        }
    }

	//用户注册
	function user_register($username, $password, $nickname = "", $bool = false) {
		$url = "users";
		$http_method = "POST";

		$request_body_array = array (
			'username'=>$username,
			'password'=>$password
		);
		if (!empty($nickname)) {
			$request_body_array['nickname'] = $nickname;
		}
		
	        $header = array('Content-Type: application/json');
	        if($bool){
	            $token = $this->get_token();
	            $header = array('Authorization: Bearer '.$token);
	        }
	        $request_body = json_encode($request_body_array);
	
	        $result = $this->api_request($url,$request_body , $http_method, $header);
	        
	        return $this->result_handler($result);
	}

    //删除环信用户
    function user_delete($usesrnames){
        if(!is_array($usesrnames)){
            $usesrnames = array($usesrnames);
        }

        $token = $this->get_token();
        $header = array('Authorization: Bearer '.$token);

        $result_array = array();
        foreach($usesrnames as $username){
            $result_array[$username] = false;

            $path = 'users/'.$username;
            $result = $this->api_request($path, '' , 'DELETE', $header);
            $ret = $this->result_handler($result);
            if($ret!==false){
                $result_array[$username] = true;
            }
        }

        return $result_array;
    }

    /**
     * 获取聊天记录
     * @param int $timestamp 大于什么timestamp
     * @param string $cursor
     * @return bool
     */
    function get_message($timestamp = 0 ,$cursor = ''){
        $token = $this->get_token();
        $path = 'chatmessages';
        $where = $timestamp == 0 ? '' : '+where+timestamp>'.$timestamp;
        $sql = 'ql=select+*'.$where.'+order+by+timestamp+asc&limit=20';
        $header = array('Content-Type: application/json','Authorization: Bearer '.$token);
        $result = $this->api_request($path, $sql.($cursor==''?'':'&cursor='.$cursor) , 'GET',$header);
        return $this->result_handler($result);
    }

    /**
     * @param array $target 发送的目标，当type为users的时候，为username，当type为chatgroups的时候，为groupid
     * @param array $msg 消息数组，分为文字 / 图片 / 视频等
     * @param string $target_type 发送消息类型： users 给用户发消息, chatgroups 给群发消息
     * @param string $from 发送人名称，当为空得时候，显示admin
     * @param array $ext 额外参数
     * @return bool | json 错误直接返回false
     */
    function send_message($target,$msg,$target_type='users',$from='',$ext=array()){
        $target_type = in_array($target_type,array('users','chatgroups')) ? $target_type : 'users';
        if(!is_array($target)){
            self::$errmsg = '$target参数必须为数组';
            return false;
        }
        if(!is_array($msg)){
            self::$errmsg = '$msg参数必须为数组';
            return false;
        }
        $request_body = array(
            'target_type' => $target_type,
            'target' => $target,
            'msg' => $msg
        );
        if($from!=''){
            $request_body['from'] = $from;
        }
        if(!empty($ext) && is_array($ext)){
            $request_body['ext'] = $ext;
        }

        $token = $this->get_token();
        $path = 'messages';
        $header = array('Content-Type: application/json','Authorization: Bearer '.$token);
        $result = $this->api_request($path, json_encode($request_body,JSON_UNESCAPED_UNICODE) , 'POST', $header);
        return $this->result_handler($result);

    }

    /**
     * 上传聊天文件 （图片 / 视频 / 语音）
     * @param $filepath
     * @param bool $restrict_access
     * @return bool
     */
    function upload_chatfiles($filepath , $restrict_access = false){
        $token = $this->get_token();
        $path = 'chatfiles';
        $header = array('Authorization: Bearer '.$token);
        if($restrict_access){
            $header[] = 'restrict-access:true';
        }

        $upload_file = new \CURLFile($filepath);

//        echo $files;
        $request_body = array(
            'file' => $upload_file
        );

        $result = $this->api_request($path, $request_body , 'POST', $header, true);
        return $this->result_handler($result);
    }

    /**
     * 修改昵称
     * @param $username
     * @param $nickname
     * @return bool
     */
    function set_nickname($username , $nickname){
        if(empty($nickname) || empty($username)){
            return false;
        }
        $token = $this->get_token();
        $header = array('Authorization: Bearer '.$token);
        $path = 'users/'.$username;
        $request_body = array("nickname"=>$nickname);
        $result = $this->api_request($path, json_encode($request_body,JSON_UNESCAPED_UNICODE) , 'PUT', $header);
        return $this->result_handler($result);
    }

    /**
     * 修改密码
     * @param $username
     * @param $newpassword
     * @return bool
     */
    function reset_password($username,$newpassword){
        if(empty($username) || empty($newpassword)){
            return false;
        }
        $token = $this->get_token();
        $header = array('Authorization: Bearer '.$token);
        $path = 'users/'.$username.'/password';
        $request_body = array("newpassword"=>$newpassword);

        //确保执行成功
        $return = false;
        while($return===false){
            $result = $this->api_request($path, json_encode($request_body,JSON_UNESCAPED_UNICODE) , 'PUT', $header);
            $return = $this->result_handler($result);
        }

        return $return;
    }

    //结果处理
    function result_handler($api_result){
        if(!is_array($api_result)){
            return false;
        }

        list($status_code, $reponse_body) = $api_result;

        if ($status_code == "200") {
            return $reponse_body;
        } else {

            //解析返回错误
            $err_array = json_decode($reponse_body, true);
            $errmsg = '未知错误';
            if (isset($err_array["error"]) && isset($err_array["error_description"])) {

                $errors = self::$error_code;
                foreach($errors as $key=>$val){
                    if(strripos($key,$err_array['error'])===false){
                        continue;
                    }
                    if(is_array($val)){
                        foreach ($val as $v) {
                            if(strripos($err_array["error_description"],$v)!==false){
                                $errmsg = $v;
                                break;
                            }
                        }
                    }else{
                        $errmsg = $val;
                    }
                }
            }

            //记录错误
            self::$errmsg = $status_code.'；'.$errmsg;
//            exit($status_code.'错误：'.$errmsg);
            return false;
        }
    }

    //获取错误信息
    function get_errmsg(){
        return self::$errmsg;
    }
};
?>
