<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: Cart控制器模块
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\index\model\Cart as CartModel;
use think\Db;
class Cart extends Bace{
    
    public $cart_model;          //购物车逻辑操作类
    public $user_id = 0;        //user_id
    public $user = array();     //用户信息
    
    /**
     * @desc   Cart控制器初始化
     * @access public
     * @param  null
     * @return null
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->cart_model = new CartModel();
        //判断用户是否登录
        if (session('?user')) {
            $user = session('user');
            $user = Db::table('tp_users')->where("user_id = {$user['user_id']}")->find();
            session('user', $user);  
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); 
            //给用户计算会员价,登录前后不一样
            if ($user) {
                $user['discount'] = (empty($user['discount'])) ? 1 : $user['discount'];
                if($user['discount'] != 1) {
                    $c = Db::table('tp_cart')
                        ->where(['user_id' => $user['user_id'], 'prom_type' => 0])
                        ->where('member_goods_price = goods_price')
                        ->count();
                    $c && Db::table('tp_cart')
                        ->where(['user_id' => $user['user_id'], 'prom_type' => 0])
                        ->update(['member_goods_price' => ['exp', 'goods_price*' . $user['discount']]]);
                }
            }
        }
    }
    /**
     * @desc   跳转到购物车列表
     * @access public
     * @param  null
     * @return null
     */
    public function toCart(){
        $m = D('Home/Cart');
        $cartInfo = $m->getCartInfo();
        $pnow = (int)I("pnow",0);
        $this->assign('cartInfo',$cartInfo);
        $this->display('cart_pay_list');
    
    }
    
    /**
     * 添加商品到购物车(ajax)
     */
    public function addToCartAjax(){
        $m = D('Home/Cart');
        $rs = $m->addToCart();
        $this->ajaxReturn($rs);
    }
    
    /**
     * 添加优惠套餐到购物车(ajax)
     */
    public function addCartPackage(){
        $m = D('Home/Cart');
        $rs = $m->addCartPackage();
        $this->ajaxReturn($rs);
    }
    
    /**
     * 修改购物车商品
     *
     */
    public function changeCartGoods(){
        $m = D('Home/Cart');
        $res = $m->addToCart();
        echo "{status:1}";
    }    
    /**
     * @desc   获取购物车信息
     * @access public
     * @param  null
     * @return null
     */
    public function getCartInfo() {
        $cart = $this->cart_model;
        $cart_info = $cart->getCartInfo();
        dump($cart_info);
        $axm = (int)input('axm', 0);
        if($axm ==1){
            echo json_encode($cartInfo);
        }else{
            $this->assign('cartInfo',$cart_info);
            $this->display('cart_pay_list');
        }    
    }
    
    /**
     * 获取购物车商品数量
     */
    public function getCartGoodCnt(){
        echo json_encode(array("goodscnt"=>WSTCartNum()));
    }
    
    /**
     * 检测购物车中商品库存
     *
     */
    public function checkCartGoodsStock(){
        $m = D('Home/Cart');
        $res = $m->checkCatGoodsStock();
        echo json_encode($res);
    
    }
    
    
    
    /**
     * 删除购物车中的商品
     *
     */
    public function delCartGoods(){
        $m = D('Home/Cart');
        $res = $m->delCartGoods();
        $cartInfo = $m->getCartInfo();
        echo json_encode($cartInfo);
    }
    
    /**
     * 删除购物车中的商品
     *
     */
    public function delPckCatGoods(){
        $m = D('Home/Cart');
        $res = $m->delPckCatGoods();
        $cartInfo = $m->getCartInfo();
        echo json_encode($cartInfo);
    }
    
    /**
     * 修改购物车中的商品数量
     *
     */
    public function changeCartGoodsNum(){
    
        $data = array();
        $data['goodsId'] = (int)I('goodsId');
        $data['isBook'] = (int)I('isBook');
        $data['goodsAttrId'] = (int)I('goodsAttrId');
        $goods = D('Home/Goods');
        $goodsStock = $goods->getGoodsStock($data);
        $num = (int)I("num");
        if($goodsStock["goodsStock"]>=$num){
            $num = $num>100?100:$num;
        }else{
            $num = (int)$goodsStock["goodsStock"];
        }
        $m = D('Home/Cart');
        $rs = $m->changeCartGoodsnum(abs($num));
        $this->ajaxReturn($goodsStock);
    
    }
    
    /**
     * 修改购物车中的商品数量
     *
     */
    public function changePkgCartGoodsNum(){
    
        $data = array();
        $data['packageId'] = (int)I('packageId');
        $data['batchNo'] = (int)I('batchNo');
        $goods = D('Home/Goods');
        $goodsStock = $goods->getPkgGoodsStock($data);
        $num = (int)I("num");
        if($goodsStock["goodsStock"]>=$num){
            $num = $num>100?100:$num;
        }else{
            $num = (int)$goodsStock["goodsStock"];
        }
        $m = D('Home/Cart');
        $rs = $m->changePkgCartGoodsNum(abs($num));
        $this->ajaxReturn($goodsStock);
    
    }
    
    /**
     *去购物车结算
     *
     */
    public function toCatpaylist(){
        $m = D('Home/Cart');
        $cartInfo = $m->getCartInfo();
        $this->assign("cartInfo",$cartInfo);
    
        $this->display('cat_pay_list');
    }
}