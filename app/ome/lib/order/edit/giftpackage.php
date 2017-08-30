<?php
/**
 * 订单编辑时礼包商品处理功能的实现方法
 * 对订单编辑的提交数据进行操作
 * @author chris.zhang
 * @package ome_order_edit_giftpackage
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_order_edit_giftpackage{
    
    protected  $config = array(
        'app'       => 'ome',
        'html'      => 'admin/order/edit/giftpackage_edit.html',
        'js_count'  => 'total_giftpackage()',
        'is_add'    => false,
        'add_title' => '',
        'add_id'    => '',
    );
    
    /**
     * 获取捆绑商品类型页面配置
     * @return Araay conf
     */
    public function get_config(){
        return $this->config;
    }
    
    /**
     * 处理订单编辑时提交的数据
     * @access public
     * @param array $data 订单编辑的数据  //POST
     */
    public function process($data){
        if (!$data['giftpackage']) return false;
        
        $obj        = $data['giftpackage']['obj'];
        $onum       = $data['giftpackage']['num'];
        $oprice     = $data['giftpackage']['price'];
        $num        = $data['giftpackage']['inum'];
        $price      = $data['giftpackage']['iprice'];
        $order_id   = $data['order_id'];
        
        $pObj       = &app::get('ome')->model('products');
        $oOrderItm  = &app::get('ome')->model("order_items");
        $oOrderObj  = &app::get('ome')->model("order_objects");
        
        $tmp_obj = array();
        $new_obj = array();
        
        $is_order_change = false;
        $is_goods_modify = false;
        $total = 0;
        //捆绑商品信息
        if ($obj && is_array($obj))
        foreach ($obj as $k =>$v){
            $tmp_obj[$k] = array('obj_id'=>$k);
            $obj_ = $oOrderObj->dump($k);
            if (!$obj_) continue;
            if (isset($onum[$k])){
                $tmp_amount = intval($onum[$k])*$oprice[$k];
                $total += $tmp_amount;
                if ($obj_['amount'] != $tmp_amount){
                    $is_order_change = true;
                    $is_goods_modify = true;
                }
                $tmp_obj[$k]['obj_id']      = $k;
                $tmp_obj[$k]['amount']      = $tmp_amount;
                $tmp_obj[$k]['quantity']    = intval($onum[$k]);
                $tmp_obj[$k]['price']       = $oprice[$k];
                foreach ($v as $n){
                    $quantity = 0;
                    $oi = $oOrderItm->dump($n);
                    $quantity = intval($oi['quantity'] / $obj_['quantity'])*intval($onum[$k]);

                    if ($oi['quantity'] != $quantity){
                        $t_n = $quantity - $oi['quantity'];
                        if ($oi['delete'] == 'true'){
                            $pObj->chg_product_store_freeze($oi['product_id'],$quantity,"+");//增加冻结库存
                        }else {
                            if ($t_n < 0){
                                $pObj->chg_product_store_freeze($oi['product_id'],abs($t_n),"-");//减少冻结库存
                            }elseif ($t_n > 0){
                                $pObj->chg_product_store_freeze($oi['product_id'],abs($t_n),"+");//增加冻结库存
                            }
                        }
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }elseif ($oi['delete'] == 'true'){
                        $pObj->chg_product_store_freeze($oi['product_id'],$quantity,"+");//增加冻结库存
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }

                    $tmp_obj[$k]['items'][$n]['item_id']    = $n;
                    $tmp_obj[$k]['items'][$n]['quantity']   = $quantity;
                    $tmp_obj[$k]['items'][$n]['price']      = $price[$n]==0?$oi['price']:$price[$n];
                    $tmp_obj[$k]['items'][$n]['delete']     = 'false';
                }
            }else {
                foreach ($v as $n){
                    $oi = $oOrderItm->dump($n);
                    if ($oi['delete'] == 'false'){
                        $pObj->chg_product_store_freeze($oi['product_id'],$oi['quantity'],"-");//减少冻结库存
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }
                    $tmp_obj[$k]['items'][$n]['item_id'] = $n;
                    $tmp_obj[$k]['items'][$n]['delete']  = 'true';
                }
            }
        }
        
        $rs = array(
            'oobj'  => $tmp_obj,
            'nobj'  => $new_obj,
            'total' => $total,
            'is_order_change' => $is_order_change,
            'is_goods_modify' => $is_goods_modify,
        );
        return $rs;
    }
    
    /**
     * 判断这次提交的数据在处理完成后，是否还存在有正常的数据。
     * @param array $data 订单编辑的数据  //POST
     */
    public function is_null($data){
        if (!$data['giftpackage']) return true;
        $obj        = $data['giftpackage']['obj'];
        $onum       = $data['giftpackage']['num'];
        $oprice     = $data['giftpackage']['price'];
        if (empty($onum) || empty($oprice)) return true;
        return false;
    }
    
    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
        if (!$data['giftpackage']) return true;
        
        $obj        = $data['giftpackage']['obj'];
        $onum       = $data['giftpackage']['num'];
        $oprice     = $data['giftpackage']['price'];
        
        
        //捆绑商品信息
        if ($obj && is_array($obj))
        foreach ($obj as $k =>$v){
            $tmp_obj[$k] = array('obj_id'=>$k);
            if (isset($onum[$k])){
                if ($onum[$k] < 1  || $onum[$k] > 99999){
                    $rs = array(
                        'flag' => false,
                        'msg' => "捆绑商品数量必须大于1且小于99999",
                    );
                    return $rs;
                }
            }
        }
        return true;
    }
}