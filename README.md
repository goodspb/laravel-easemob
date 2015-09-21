## Easemob Client Provider For Laravel5

Easemob Client Provider For Laravel5


### 安装

- [Packagist](https://packagist.org/packages/goodspb/laravel-easemob)
- [GitHub](https://github.com/goodspb/laravel-easemob)

只要在你的 `composer.json` 文件require中加入下面内容，就能获得最新版.

~~~
"goodspb/laravel-easemob": "~1.0"
~~~

然后需要运行 "composer update" 来更新你的项目

安装完后，在 `app/config/app.php` 文件中找到 `providers` 键，

~~~

    Goodspb\LaravelEasemob\LaravelEasemobServiceProvider::class

~~~

找到 `aliases` 键，

~~~
'aliases' => array(

    'Easemob' => Goodspb\LaravelEasemob\Facades\Easemob::class

)
~~~

## 配置
运行以下命令发布配置文件
~~~
php artisan vendor:publish
~~~
然后到config目录下修改easemob.php
~~~
'domain'         => env('easemob_domain', ''),          //域名
'org_name'       => env('easemob_org_name', ''),        //公司名称
'app_name'       => env('easemob_app_name', ''),        //应用名称
'client_id'      => env('easemob_client_id', ''),
'client_secret'  => env('easemob_client_secret', ''),
~~~

## 使用
1、注册用户：user_register
~~~
$result = Easemob::user_register('username','password');
var_dump($result);
~~~

2、删除用户：user_delete
~~~
Easemob::user_delete('username');
~~~

3、导出聊天记录：get_message($timestamp = 0 ,$cursor = '')
~~~
$list = Easemob::get_message();
var_dump($list);
~~~

4、群发信息: send_message( $target,$msg,$target_type='users',$from='',$ext=array() )
~~~
/**
 * @param array $target 发送的目标，当type为users的时候，为username，当type为chatgroups的时候，为groupid
 * @param array $msg 消息数组，分为文字 / 图片 / 视频等
 * @param string $target_type 发送消息类型： users 给用户发消息, chatgroups 给群发消息
 * @param string $from 发送人名称，当为空得时候，显示admin
 * @param array $ext 额外参数
 * @return bool | json 错误直接返回false
 */

$target = array(
    'user1','user2','user3'
);

//群发文字信息
$msg = array(
    'type' => "txt",
    'msg'  => "I am hero"
);

//群发图片信息
$msg = array(
    "type" => "img",   // 消息类型
	"url" => "https://a1.easemob.com/easemob-demo/chatdemoui/chatfiles/55f12940-64af-11e4-8a5b-ff2336f03252",  //成功上传文件返回的uuid
	"filename" => "24849.jpg", // 指定一个文件名
	"secret" => "VfEpSmSvEeS7yU8dwa9rAQc-DIL2HhmpujTNfSTsrDt6eNb_", // 成功上传文件后返回的secret
	"size" => array(
        "width" => 480,
        "height" => 720
    )
);

//其余请查看官方文档

$result = Easemob::send_message($target,$msg);
if($result === false){
    //发送成功
}

~~~

5、修改用户昵称： set_nickname($username , $nickname)
~~~
Eaemob::set_nickname($username , $nickname);
~~~

6、重置用户密码：reset_password($username,$newpassword)
~~~
Easemob::reset_password($username,$newpassword);
~~~

7、上传聊天文件（图片、视频、语音），此API用户将图片或视频上传到环信服务器，才能群发图片信息、语音信息或视频信息
~~~
/**
 * 上传聊天文件 （图片 / 视频 / 语音）
 * @param $filepath 文件所在目录，建议先上传到服务器，然后再上传去环信服务器
 * @param bool $restrict_access  是否设置权限（仅指定用户才能查看）
 * @return bool 
 */

$result = upload_chatfiles($filepath , $restrict_access = false)；
~~~
