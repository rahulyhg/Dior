<?php
/**
 * 订单编辑时捆绑商品处理功能的实现方法
 * 对订单编辑的提交数据进行操作
 * @author chris.zhang
 * @package ome_order_edit
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_order_edit_pkg{
    
    protected  $config = array(
        'app'       => 'ome',
        'html'      => 'admin/order/edit/pkg_edit.html',
        'js_count'  => 'total_pkg()',
        'is_add'    => true,
        'add_title' => '添加捆绑商品',
        'add_id'    => 'add_pkg_product',
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
        if (!$data['pkg']) return false;
        
        $obj        = $data['pkg']['obj'];
        $onum       = $data['pkg']['num'];
        $oprice     = $data['pkg']['price'];
        $obj_pmt_price    = $data['pkg']['obj_pmt_price'];
        $item_pmt_price   = $data['pkg']['item_pmt_price'];
        $num        = $data['pkg']['inum'];
        $price      = $data['pkg']['iprice'];
        $order_id   = $data['order_id'];
        $newPkgONum = $data['pkg']['pkgonum'];//新增PKG
        $newPkgOPr  = $data['pkg']['pkgopr'];
        $new_obj_pmt_price  = $data['pkg']['new_obj_pmt_price'];
        $new_item_pmt_price  = $data['pkg']['new_item_pmt_price'];        
        $newPkgINum = $data['pkg']['pkgnum'];
        $newPkgIPr  = $data['pkg']['pkgpr'];
        
        $pObj       = &app::get('ome')->model('products');
        $oPkgG      = &app::get('omepkg')->model("pkg_goods");
        $oPkgP      = &app::get('omepkg')->model("pkg_product");
        $oOrderItm  = &app::get('ome')->model("order_items");
        $oOrderObj  = &app::get('ome')->model("order_objects");
        
        $tmp_obj = array();
        $new_obj = array();
        
        $is_order_change = false;
        $is_goods_modify = false;
        $total = $total_pmt_goods = 0;
        //捆绑商品信息
        if ($obj && is_array($obj))
        foreach ($obj as $k =>$v){
            $tmp_obj[$k] = array('obj_id'=>$k,'pmt_price'=>$obj_pmt_price[$k]);
            $obj_ = $oOrderObj->dump($k);
            if (!$obj_) continue;
            if ($obj_['quantity'] != $onum[$k] || $obj_['price'] != $oprice[$k] || $obj_['pmt_price'] != $obj_pmt_price[$k]){
                $is_order_change = true;
                $is_goods_modify = true;
            }
            if (isset($onum[$k])){
                $tmp_amount = intval($onum[$k])*$oprice[$k];
                $total += $tmp_amount;
                $total_pmt_goods += $obj_pmt_price[$k];
                if ($obj_['amount'] != $tmp_amount){
                    $is_order_change = true;
                    $is_goods_modify = true;
                }
                $tmp_obj[$k]['obj_id']           = $k;
                $tmp_obj[$k]['amount']           = $tmp_amount;
                $tmp_obj[$k]['quantity']         = intval($onum[$k]);
                $tmp_obj[$k]['price']            = $oprice[$k];
                $tmp_obj[$k]['amount']  = $tmp_obj[$k]['quantity'] * $tmp_obj[$k]['price'];
                $tmp_item_sale_price = $tmp_item_pmt_price = $tmp_item_amount = 0;
                foreach ($v as $n){
                    $quantity = 0;
                    $oi = $oOrderItm->dump($n);
                    $pmt_price = $item_pmt_price[$n] ? $item_pmt_price[$n] : '0';
                    $tmp_item_pmt_price += $pmt_price;
                    $total_pmt_goods += $pmt_price;
                    $quantity = intval($oi['quantity'] / $obj_['quantity'])*intval($onum[$k]);
                    $item_price = $price[$n]==0 ? $oi['price'] : $price[$n];
                    $sale_price = $quantity * $item_price - $pmt_price;
                    $tmp_item_sale_price += $sale_price;
                    $tmp_item_amount += $quantity * $item_price;

                    if ($oi['quantity'] != $quantity || $oi['price'] != $item_price || $oi['pmt_price'] != $pmt_price){
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
                    $tmp_obj[$k]['items'][$n]['pmt_price']  = $pmt_price;
                    $tmp_obj[$k]['items'][$n]['sale_price']  = $sale_price;

                }

                $tmp_obj[$k]['sale_price']  = $tmp_obj[$k]['amount'] - $obj_pmt_price[$k] - $tmp_item_pmt_price;
                if ($obj_['sale_price'] != $tmp_obj[$k]['sale_price']){
                    $is_order_change = true;
                    $is_goods_modify = true;
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
        
        //新增捆绑商品
        if ($newPkgONum){
            $is_order_change = true;
            $is_goods_modify = true;
            foreach ($newPkgONum as $key => $n){
                if ($data['do_action'] != 2){
                    if ($n < 1 || $n > 499999){
                        trigger_error('数量必须大于1且小于499999', E_USER_ERROR);
                    }
                }
                $g   = $oPkgG->dump($key);
                $tmp = array();
                $tmp['order_id']    = $order_id;
                $tmp['obj_type']    = 'pkg';
                $tmp['obj_alias']   = '捆绑商品';
                $tmp['goods_id']    = $key;
                $tmp['bn']          = $g['pkg_bn'];
                $tmp['name']        = $g['name'];
                $tmp['price']       = $newPkgOPr[$key];
                $tmp['pmt_price']   = $new_obj_pmt_price[$key];
                $tmp['quantity']    = $n;
                $tmp['amount']      = $n*$newPkgOPr[$key];
                $tmp['weight']      = $g['weight'];
                $total += $tmp['amount'];
                $total_pmt_goods += $new_obj_pmt_price[$key];
                $i = 0;
                $obj_amount = $obj_sale_price = $tmp_item_pmt_price = 0;
                foreach ($newPkgINum[$key] as $k => $v){
                    if ($data['do_action'] != 2){
                        if ($v < 1 || $v > 499999){
                            trigger_error('数量必须大于1且小于499999', E_USER_ERROR);
                        }
                    }
                    $p = $pObj->dump($k);
                    $tmp_price = $newPkgIPr[$key][$k];
                    $amount = $tmp_price * $v;
                    $pmt_price = $new_item_pmt_price[$key][$k];
                    $sale_price = $amount - $pmt_price;
                    $tmp_item_pmt_price += $pmt_price;
                    $obj_sale_price += $sale_price;
                    $total_pmt_goods += $pmt_price;

                    $tmp['items'][$i]['order_id']   = $order_id;
                    $tmp['items'][$i]['product_id'] = $k;
                    $tmp['items'][$i]['bn']         = $p['bn'];
                    $tmp['items'][$i]['name']       = $p['name'];
                    $tmp['items'][$i]['price']      = $tmp_price;
                    $tmp['items'][$i]['quantity']   = $v;
                    $tmp['items'][$i]['item_type']  = 'pkg';
                    $tmp['items'][$i]['amount']     = $amount; 
                    $tmp['items'][$i]['cost']       = $p['cost'];
                    $tmp['items'][$i]['weight']     = $p['weight'];
                    $tmp['items'][$i]['sale_price'] = $sale_price;
                    $tmp['items'][$i]['pmt_price']  = $pmt_price;
                    $i++;
                    $pObj->chg_product_store_freeze($k,$n,"+");
                }
                $tmp['sale_price'] = $tmp['amount'] - $new_obj_pmt_price[$key] - $tmp_item_pmt_price;
                $new_obj[] = $tmp;
            }
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
        if (!$data['pkg']) return true;
        $obj        = $data['pkg']['obj'];
        $onum       = $data['pkg']['num'];
        $oprice     = $data['pkg']['price'];
        $num        = $data['pkg']['inum'];
        $price      = $data['pkg']['iprice'];
        $newPkgONum = $data['pkg']['pkgonum'];//新增PKG
        $newPkgOPr  = $data['pkg']['pkgopr'];
        $newPkgINum = $data['pkg']['pkgnum'];
        $newPkgIPr  = $data['pkg']['pkgpr'];
        if ((empty($onum) && empty($newPkgONum)) || (empty($oprice) && empty($newPkgOPr))) return true;
        return false;
    }
    
    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
        if (!$data['pkg']) return true;
        
        $obj        = $data['pkg']['obj'];
        $onum       = $data['pkg']['num'];
        $oprice     = $data['pkg']['price'];
        $obj_pmt_price     = $data['pkg']['obj_pmt_price'];
        $item_pmt_price    = $data['pkg']['item_pmt_price'];
        $new_obj_pmt_price    = $data['pkg']['new_obj_pmt_price'];
        $new_item_pmt_price   = $data['pkg']['new_item_pmt_price'];
        $num        = $data['pkg']['inum'];
        $price      = $data['pkg']['iprice'];
        $newPkgONum = $data['pkg']['pkgonum'];//新增PKG
        $newPkgOPr  = $data['pkg']['pkgopr'];
        $newPkgINum = $data['pkg']['pkgnum'];
        $newPkgIPr  = $data['pkg']['pkgpr'];
        
        
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

            $tmp_item_sale_price = 0;
            foreach ($v as $item_id){
                //if ($item_pmt_price[$item_id] < 0 || $item_pmt_price[$item_id] > $num[$item_id]*$price[$item_id]){
                if ($item_pmt_price[$item_id] < 0){
                    $rs = array(
                        'flag' => false,
                        'msg' => "优惠金额必须大于等于0",
                        'error_info' => array('name'=>'pkg[item_pmt_price]['.$item_id.']'),
                    );
                    return $rs;
                }
                $tmp_item_sale_price += $item_pmt_price[$item_id];
            }
            if ($obj_pmt_price[$k] < 0){
                $rs = array(
                    'flag' => false,
                    'msg' => "优惠金额必须大于等于0",
                    'error_info' => array('name'=>'pkg[obj_pmt_price]['.$k.']'),
                );
                return $rs;
            }
            if ($obj_pmt_price[$k]+$tmp_item_sale_price > $onum[$k] * $oprice[$k]){
                $rs = array(
                    'flag' => false,
                    'msg' => "优惠金额必须小于等于销售金额",
                    'error_info' => array('name'=>'pkg[obj_pmt_price]['.$k.']'),
                );
                return $rs;
            }

        }
    
        //新增捆绑商品
        if ($newPkgONum){
            foreach ($newPkgONum as $key => $n){
                if ($n < 1 || $n > 99999){
                    $rs = array(
                        'flag' => false,
                        'msg'  => "捆绑商品数量必须大于1且小于99999",
                    );
                    return $rs;
                }
                foreach ($newPkgINum[$key] as $k => $v){
                    if ($v < 1 || $v > 99999){
                        $rs = array(
                            'flag' => false,
                            'msg'  => "捆绑商品数量必须大于1且小于99999",
                        );
                        return $rs;
                    }
                }
            }
        }
        return true;
    }
}