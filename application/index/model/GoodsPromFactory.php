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
// | @Desp: 商品活动工厂
// +----------------------------------------------------------------------
namespace app\index\model;

/**
 * 商品活动工厂类
 * Class CatsLogic
 * @package admin\Logic
 */
class GoodsPromFactory
{
    /**
     * @param   Object_Model    $goods  商品模型实例
     * @param   Object_Spec     $spec_goods_price   商品规格实例
     * @return  FlashSaleLogic|GroupBuyLogic|PromGoodsLogic
     */
    public function makeModule($goods, $spec_goods_price)
    {
        switch ($goods['prom_type']) {
            case 1:
                return new FlashSaleLogic($goods, $spec_goods_price);   //闪电购活动
            case 2:
                return new GroupBuyLogic($goods, $spec_goods_price);    //团购活动
            case 3:
                return new PromGoodsLogic($goods, $spec_goods_price);   //特价促销活动
        }
    }

    /**
     * @desc    检测是否符合商品活动工厂类的使用
     * @param   int     $promType   活动类型
     * @return  bool
     */
    public function checkPromType($promType)
    {
        if (in_array($promType, array_values([1, 2, 3]))) {
            return true;
        } else {
            return false;
        }
    }

}
