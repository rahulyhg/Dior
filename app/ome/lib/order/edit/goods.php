<?php
/**
 * 订单编辑时商品处理功能的实现方法
 * 对订单编辑的提交数据进行操作
 * @author chris.zhang
 * @package ome_order_edit
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_order_edit_goods{

    protected  $config = array(
        'app'       => 'ome',
        'html'      => 'admin/order/edit/goods_edit.html',
        'js_count'  => 'total_goods()',
        'is_add'    => true,
        'add_title' => '添加商品',
        'add_id'    => 'add_product',
    );

    /**
     * 获取商品类型页面配置
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
        if (!$data['goods']) return false;

        $obj        = $data['goods']['obj'];
        $num        = $data['goods']['num'];
        $price      = $data['goods']['price'];
        $obj_pmt_price  = $data['goods']['obj_pmt_price'];
        $item_pmt_price  = $data['goods']['item_pmt_price'];
        $new_num    = $data['goods']['newnum'];
        $new_price  = $data['goods']['newprice'];
        $new_item_pmt_price  = $data['goods']['new_item_pmt_price'];
        $order_id   = $data['order_id'];


        $pObj       = &app::get('ome')->model('products');
        $oOrderItm  = &app::get('ome')->model("order_items");
        $oOrderObj  = &app::get('ome')->model("order_objects");

        $tmp_obj = array();
        $new_obj = array();

        $is_order_change = false;
        $is_goods_modify = false;

        $total = 0;
		$total_pmt_goods = 0;

        if ($obj && is_array($obj))
        foreach ($obj as $k => $v){
            $tmp_obj[$k] = array('obj_id'=>$k,'pmt_price'=>$obj_pmt_price[$k]);
            $tmp_amount  = 0;
            $tmp_item_sale_price = 0;
            foreach ($v as $n){
                $oi = $oOrderItm->dump($n);
                if (!$oi) continue;
                if (isset($num[$n])){
                    if ($data['do_action'] != 2){
                        if ($num[$n] < 1 || $num[$n] > 499999){
                            trigger_error('数量必须大于1且小于499999', E_USER_ERROR);
                        }
                    }
                    if ($oi['pmt_price'] != $item_pmt_price[$n]){
                        $is_order_change = true;
                        $is_goods_modify = true;
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

                    $tmp_price = $price[$n]==0?0:$price[$n];
                    $pmt_price = $item_pmt_price[$n]==0?0:$item_pmt_price[$n];
                    $tmp_item = array (
                        'item_id'   => $n,
                        'quantity'  => $num[$n],
                        'price'     => $tmp_price,
                        'pmt_price'     => $pmt_price,
                        'delete'    => 'false',
                    );
                    $tmp_item['amount'] = $tmp_item['quantity'] * $tmp_item['price'];
                    $tmp_item['sale_price'] = $tmp_item['amount'] - $pmt_price;
                    $tmp_item_sale_price += $tmp_item['sale_price'];
                    $total += $tmp_item['amount'];
                    $total_pmt_goods += $pmt_price;
                    $tmp_obj[$k]['amount'] += $tmp_item['amount'];
                    //$tmp_obj[$k]['pmt_price'] = $pmt_price;
                    $tmp_obj[$k]['price'] = $tmp_item['price'];
                    $tmp_obj[$k]['quantity'] = $tmp_item['quantity'];


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

            $total_pmt_goods += $obj_pmt_price[$k];
            $tmp_obj[$k]['sale_price'] = $tmp_item_sale_price - $obj_pmt_price[$k];

        }

        //普通商品
        if ($new_num){
            $add_obj = array();
            foreach ($new_num as $key => $n){
                if ($data['do_action'] != 2){
                    if ($n < 1 || $n > 499999){
                        trigger_error('数量必须大于1且小于499999', E_USER_ERROR);
                    }
                }
                //$tmp = array();
                $p = $pObj->dump($key);
                $tmp_price = $new_price[$key];
                $pmt_price = $new_item_pmt_price[$key];
                $amount = $n * $tmp_price;
                $tmp['order_id']    = $order_id;
                $tmp['obj_type']    = 'goods';
                $tmp['obj_alias']   = '商品';
                $tmp['goods_id']    = $p['goods_id'];
                $tmp['bn']          = $p['bn'];
                $tmp['name']        = $p['name'];
                $tmp['price']       = $tmp_price;
                $tmp['quantity']    = $n;
                $tmp['amount']      = $n*$new_price[$key];
                $tmp['weight']      = $p['weight'];
                $tmp['pmt_price']   = 0.00;
                $tmp['sale_price']  = $amount - $pmt_price;

                $tmp['items'][0]['order_id']    = $order_id;
                $tmp['items'][0]['product_id']  = $key;
                $tmp['items'][0]['bn']          = $p['bn'];
                $tmp['items'][0]['name']        = $p['name'];
                $tmp['items'][0]['price']       = $new_price[$key];
                $tmp['items'][0]['quantity']    = $n;
                $tmp['items'][0]['item_type']   = 'product';
                $tmp['items'][0]['amount']      = $amount;
                $tmp['items'][0]['cost']        = $p['cost'];
                $tmp['items'][0]['weight']      = $p['weight'];
                $tmp['items'][0]['sale_price']  = $amount - $pmt_price;
                $tmp['items'][0]['pmt_price']   = $pmt_price;

                $new_obj[] = $tmp;
                $total += $tmp['amount'];
                $total_pmt_goods += $pmt_price;
                $pObj->chg_product_store_freeze($key,$n,"+");//增加冻结库存
            }
            $is_order_change = true;
            $is_goods_modify = true;
        }
        $rs = array(
            'oobj'  => $tmp_obj,
            'nobj'  => $new_obj,
            'total' => $total,
            'is_order_change' => $is_order_change,
            'is_goods_modify' => $is_goods_modify,
            'total_pmt_goods' => $total_pmt_goods,
        );

        return $rs;
    }

    /**
     * 判断这次提交的数据在处理完成后，是否还存在有正常的数据。
     * @param array $data 订单编辑的数据  //POST
     */
    public function is_null($data){
        if (!$data['goods']) return true;
        $obj        = $data['goods']['obj'];
        $num        = $data['goods']['num'];
        $price      = $data['goods']['price'];
        $new_num    = $data['goods']['newnum'];
        $new_price  = $data['goods']['newprice'];
        if ((empty($num) && empty($new_num)) || (empty($price) && empty($new_price))) return true;
        return false;
    }

    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
    if (!$data['goods']) return true;

        $obj        = $data['goods']['obj'];
        $num        = $data['goods']['num'];
        $price      = $data['goods']['price'];
        $obj_pmt_price  = $data['goods']['obj_pmt_price'];
        $item_pmt_price  = $data['goods']['item_pmt_price'];
        $new_num    = $data['goods']['new_num'];
        $new_price  = $data['goods']['new_price'];
        $new_item_pmt_price  = $data['goods']['new_item_pmt_price'];

        if ($obj && is_array($obj))
        foreach ($obj as $k => $v){
            $tmp_item_sale_price = 0;
            foreach ($v as $n){
                if (isset($num[$n])){
                    if ($num[$n] < 1  || $num[$n] > 499999){
                        $rs = array(
                            'flag'  => false,
                            'msg'   => "商品数量必须大于1且小于499999",
                        );
                        return $rs;
                    }
                }
                $amount = $num[$n] * $price[$n];
                if ($item_pmt_price[$n] < 0  || $item_pmt_price[$n] > $amount){
                    $rs = array(
                        'flag' => false,
                        'msg' => "优惠金额必须大于等于0且小于等于销售金额",
                        'error_info' => array('name'=>'goods[item_pmt_price]['.$n.']'),
                    );
                    return $rs;
                }
                $tmp_item_sale_price += $amount - $item_pmt_price[$n];
            }

            if ($obj_pmt_price[$k] < 0 || $obj_pmt_price[$k] > $tmp_item_sale_price){
                $rs = array(
                    'flag' => false,
                    'msg' => "优惠金额必须大于等于0且小于等于销售金额",
                    'error_info' => array('name'=>'goods[obj_pmt_price]['.$k.']'),
                );
                return $rs;
            }

        }

        //普通商品
        if ($new_num)
        foreach ($new_num as $key => $n){
            if ($n < 1 || $n > 499999){
                $rs = array(
                    'flag'  => false,
                    'msg'   => "商品数量必须大于1且小于499999",
                );
                return $rs;
            }

            $amount = $n * $new_price[$key];
            if ($new_item_pmt_price[$key] < 0  || $new_item_pmt_price[$key] > $amount){
                $rs = array(
                    'flag' => false,
                    'msg' => "优惠金额必须大于等于0且小于等于销售金额",
                    'error_info' => array('name'=>'goods[new_item_pmt_price]['.$key.']'),
                );
                return $rs;
            }

        }
        return true;
    }
}