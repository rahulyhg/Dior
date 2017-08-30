<?php
/**
 * 订单编辑时赠品处理功能的实现方法
 * 对订单编辑的提交数据进行操作
 * @author chris.zhang
 * @package ome_order_edit
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_order_edit_gift{
    
    protected  $config = array(
        'app'       => 'ome',
        'html'      => 'admin/order/edit/gift_edit.html',
        'js_count'  => 'total_gift()',
        'is_add'    => false,
        'add_title' => '',
        'add_id'    => '',
    );
    
    /**
     * 获取赠品类型页面配置
     * @return Araay conf
     */
    public function get_config(){
        return $this->config;
    }
    /**
     * 处理订单编辑时提交的数据
     * @access public
     * @param array $data 订单编辑的数据
     */
    public function process($data){
        if (!$data['gift']) return false;
        
        $obj        = $data['gift']['obj'];
        $num        = $data['gift']['num'];
        $price      = $data['gift']['price'];
        $order_id   = $data['order_id'];
        
        $pObj       = &app::get('ome')->model('products');
        $oOrderItm  = &app::get('ome')->model("order_items");
        $oOrderObj  = &app::get('ome')->model("order_objects");
        
        $tmp_obj = array();
        $new_obj = array();
        $total = 0;
        if ($obj && is_array($obj))
        foreach ($obj as $k => $v){
            $tmp_obj[$k] = array('obj_id'=>$k);
            $tmp_amount  = 0;
            foreach ($v as $n){
                $oi = $oOrderItm->dump($n);
                if (!$oi) continue;
                if (isset($num[$n])){
                    if ($data['do_action'] != 2){
                        if ($num[$n] < 1 || $num[$n] > 499999){
                            trigger_error('数量必须大于1且小于499999', E_USER_ERROR);
                        }
                    }
                    if ($oi['quantity'] != $num[$n] || $oi['price'] != $price[$n]){
                        $t_n = $num[$n] - $oi['quantity'];
                        if ($oi['delete'] == 'true'){
                            $pObj->chg_product_store_freeze($oi['product_id'],$num[$n],"+");//增加冻结库存
                        }else {
                            if ($t_n < 0){
                                $pObj->chg_product_store_freeze($oi['product_id'],abs($t_n),"-");//减少冻结库存
                            }elseif ($t_n > 0){
                                $pObj->chg_product_store_freeze($oi['product_id'],abs($t_n),"+");//增加冻结库存
                            }
                        }
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }else if ($oi['delete'] == 'true'){
                        $pObj->chg_product_store_freeze($oi['product_id'],$num[$n],"+");//增加冻结库存
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }
                    
                    $tmp_item = array (
                        'item_id'   => $n,
                        'quantity'  => $num[$n],
                        'price'     => $price[$n]==0?0:$price[$n],
                        'delete'    => 'false',
                    );
                    $tmp_item['amount'] = $tmp_item['quantity'] * $tmp_item['price'];
                    $tmp_amount += $tmp_item['amount'];
                    $total += $tmp_amount;
                }else {
                    if ($oi['delete'] == 'false'){
                        $pObj->chg_product_store_freeze($oi['product_id'],$oi['quantity'],"-");//减少冻结库存
                        $is_order_change = true;
                        $is_goods_modify = true;
                    }
                    $tmp_item = array (
                        'item_id' => $n,
                        'delete'  => 'true',
                    );
                }
                $tmp_obj[$k]['items'][$n] = $tmp_item;
            }
            $tmp_obj[$k]['amount'] = $tmp_amount;
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
        if (!$data['gift']) return true;
        $obj    = $data['gift']['obj'];
        $num    = $data['gift']['num'];
        $price  = $data['gift']['price'];
        if (empty($num) || empty($price)) return true;
        return false;
    }
    
    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
        if (!$data['gift']) return true;
        $obj    = $data['gift']['obj'];
        $num    = $data['gift']['num'];
        $price  = $data['gift']['price'];
        
        if ($obj && is_array($obj))
        foreach ($obj as $k => $v){
            foreach ($v as $n){
                if (isset($num[$n])){
                    if ($num[$n] < 1  || $num[$n] > 499999){
                        if ($num[$n] < 1 || $num[$n] > 499999){
                            $rs = array(
                                'flag' => false,
                                'msg'  => "赠品数量必须大于1且小于499999",
                            );
                            return $rs;
                        }
                    }
                }
            }
        }
        return true;
    }
}