<?php
// +----------------------------------------------------------------------
// | WeiDo Cart购物车模型
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: 实现围兜网用户类业务逻辑层与数据层操作
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;
use phpDocumentor\Reflection\Types\Object_;
use think\Db;

class Cart extends Model
{    
    protected $goods;                       //商品模型
    protected $spec_goods_price;            //商品规格模型
    protected $goods_buy_num;               //购买的商品数量
    protected $session_id;                  //session_id
    protected $user_id = 0;                 //user_id
    protected $user_goods_type_count = 0;   //用户购物车的全部商品种类
    protected $table = "tp_cart";
    
    /**
     * @desc    Cart初始化
     * @access  public
     * @return  void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->session_id = session_id();
        if (session('?user_id')) {
            $this->user_id = session('user_id');
        }
    }
    /**
     * @desc    定义购物车Cart模型与促销活动PromGoods的一对一关联
     * @access  public
     * @return  void
     */
    public function promGoods()
    {
        return $this->hasOne('PromGoods', 'id', 'prom_id')->cache(true,10);
    }
    /**
     * @desc    定义购物车Cart模型与商品Goods的一对一关联
     * @access  public
     * @return  void
     */
    public function goods()
    {
        return $this->hasOne('Goods', 'goods_id', 'goods_id')->cache(true,10);
    }
    /**
     * @desc    获取购物车信息
     * @access  public
     * @return  array
     */
    public function getCartInfo()
    {
        $result = array();
        //获取购物车商品列表
        $result['cart_list'] = $this->getCartList();
        //获取购物车商品总数
        $result['cart_goods_total_num'] = array_sum(array_map(function($val){return $val['goods_num'];}, $result['cart_list']));
        //TODO:获取购物车商品总价格
        $result['cart_goods_total_price'] = 100;
        return $result;
    }
    /**
     * @desc    获取购物车商品列表
     * @access  public
     * @param   int     $selected   是否被用户勾选中:0为全部,1为选中   default:0
     * @return  string
     */
    public function getCartList($selected = 0)
    {
        //如果用户已经登录则按照用户id查询,否则按照session_id查询
        if ($this->user_id) {
            $cart_map['user_id'] = $this->user_id;
        } else {
            $cart_map['session_id'] = $this->session_id;
        }
        //查找是否被用户勾选的商品
        if($selected != 0){
            $cart_map['selected'] = 1;
        }
        //获取购物车上商品列表并预加载活动模型(promGoods)与商品模型(Goods)模型关联内容
        $cart_list = $this->with('promGoods,goods')->where($cart_map)->select();
        //过滤掉无效的购物车商品
        $cart_check_after_list = $this->checkCartList($cart_list);       
        return $cart_check_after_list;
    }
    /**
     * @desc    获取购物车信息
     * @access  public
     * @param   int     $user_id  用户id
     * @return  void
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }
    /**
     * @desc    用户登录后对购物车商品进行合并
     * @access  public
     * @return  void
     */
    public function doUserLoginHandle()
    {
        //判断session_id与user_id是否为空
        if (empty($this->session_id) || empty($this->user_id)) {
            return;
        }
        //登录后将当前session_id购物车的商品的 user_id改为当前登录的id       
        $this->save(['user_id' => $this->user_id], ['session_id' => $this->session_id, 'user_id' => 0]);
        //查找购物车两件完全相同的商品
        $cart_id_arr = $this->field('id')
            ->where(['user_id' => $this->user_id])
            ->group('goods_id')
            ->having('count(goods_id) > 1')
            ->select();
        if (!empty($cart_id_arr)) {
            $arr2 = array();
            foreach($cart_id_arr as $key => $val){
                $arr2[] = $val['id'];
            } 
            // 删除购物车完全相同的商品
            $this->delete($arr2); 
        }        
    }
    /**
     * @desc    过滤掉无效的购物车商品(1.商品不存在或者商品已下架;2.活动商品的活动已失效)
     * @param   Object_Cart  $cart_list 购物车列表
     * @return  void
     */
    public function checkCartList($cart_list)
    {
        $goods_prom_factory = new GoodsPromFactory();
        foreach($cart_list as $cart_key => $cart) {
            //商品不存在或者已经下架   
            if(empty($cart['goods']) || $cart['goods']['is_on_sale'] != 1){
                $cart->delete();
                unset($cart_list[$cart_key]);
                continue;
            }
            //活动商品的活动是否失效
            if ($goods_prom_factory->checkPromType($cart['prom_type'])) {
                //判断商品规格
                if (!empty($cart['spec_key'])) {
                    $spec_goods_price = SpecGoodsPrice::get(['goods_id' => $cart['goods_id'], 'key' => $cart['spec_key']], '', true);
                    //商品规格不在活动中
                    if($spec_goods_price['prom_id'] != $cart['prom_id']){
                        $cart->delete();
                        unset($cart_list[$cart_key]);
                        continue;
                    }
                } else {
                    if($cart['goods']['prom_id'] != $cart['prom_id']){
                        $cart->delete();
                        unset($cart_list[$cart_key]);
                        continue;
                    }
                    $spec_goods_price = null;
                }
                $goods_prom_logic = $goods_prom_factory->makeModule($cart['goods'], $spec_goods_price);
                if ($goods_prom_logic && !$goods_prom_logic->isAble()) {
                    $cart->delete();
                    unset($cart_list[$cart_key]);
                    continue;
                }    
            }
        }
        return $cart_list;
    }
    /**
     * @desc    删除购物车商品
     * @param   array $cart_ids
     * @return  int
     * @throws  \think\Exception
     */
    public function delete($cart_ids = array()){
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }
        $delete = Db::table('tp_cart')->where($cartWhere)->where('id','IN',$cart_ids)->delete();
        return $delete;
    }
}