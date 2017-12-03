<?php
// +----------------------------------------------------------------------
// | WeiDo 用户第三方登录接口类 
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: 实现用户第三方登录的接口
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\model\User;
use app\model\Cart;
use think\Db;

class LoginApi extends Bace {
    public $config;
    public $oauth;
    public $class_obj;

    public function __construct(){
        parent::__construct();      
        $this->oauth = input('oauth');
        //获取插件配置
        $data = Db::table('tp_plugin')->where("code", $this->oauth)->where("type", "login")->find();
        //配置反序列化
        $this->config = unserialize($data['config_value']); 
        if(!$this->oauth)
            $this->error('非法操作', url('user/login'));
        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        $class = '\\'.$this->oauth;
        //实例化对应的登陆插件
        $this->class_obj = new $class($this->config); 
    }
    public function login(){
        if(!$this->oauth)
            $this->error('非法操作', url('user/login'));
        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        $this->class_obj->login();
    }    
    public function callback(){
        $data = $this->class_obj->respon();
        $user = new User();
        if(session('?user')) {
        	$res = $user->oauth_bind($data);//已有账号绑定第三方账号
        	if($res['status'] == 1){
        		$this->success('绑定成功',url('user/index'));
        	}else{
        		$this->error('绑定失败',url('user/index'));
        	}
        }
        $data = $user->thirdLogin($data);
        if($data['status'] != 1)
            $this->error($data['msg']);
        session('user',$data['result']);
        setcookie('user_id',$data['result']['user_id'],null,'/');
        $nickname = empty($data['result']['nickname']) ? '第三方用户' : $data['result']['nickname'];
        setcookie('nick_name',urlencode($nickname),null,'/');

        //登录后将购物车的商品的 user_id 改为当前登录的id            
        $cart = new Cart();
        $cart->setUserId($data['result']['user_id']);
        $cart->doUserLoginHandle();
        if(isMobile()) {
            $this->success('登陆成功', url('Mobile/User/index'));
        } else {
            $this->success('登陆成功', url('User/index'));
        }
}}