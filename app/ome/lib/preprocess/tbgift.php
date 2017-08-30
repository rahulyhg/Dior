<?php

class ome_preprocess_tbgift {

    static private $__local_gifts = array();

    static private $__tb_gifts = array();
    static private  $_tb_gift_order_id = null;
    public $_apiGiftLog = array();

    /**
     * 订单接收保存订单优惠赠品信息
     */
    public function save($orderid, $gift_info){
        if(!$orderid || !$gift_info || !is_array($gift_info)){
            return false;
        }
        $is_update = false;
        $orderObj = &app::get('ome')->model('orders');
        $tbgiftOrderItemsObj = &app::get('ome')->model('tbgift_order_items');
        $tmp_arr = $gift_info;
        foreach((array)$tmp_arr as $info){
            if($info['type'] == 'gift'){
                $data = array(
                    'order_id' => $orderid,
                    'outer_item_id' => $info['id'],
                    'name' => $info['name'],
                	'nums' => $info['num'],
                );
                if($tbgiftOrderItemsObj->save($data)){
                    $is_update = true;
                }
            }
        }

        if($is_update){
            $orderObj->update(array('abnormal_status'=>ome_preprocess_const::__HASGIFT_CODE),array('order_id'=>$orderid));
        }
    }

    /**
     * 处理订单追加优惠信息中的赠品
     */
    public function process($order_id,&$msg){
        if(!$order_id){
            $msg = '缺少处理参数';
            return false;
        }

        $orderObj = &app::get('ome')->model('orders');
        $tbgiftOrderItemsObj = &app::get('ome')->model('tbgift_order_items');
        $productObj = &app::get('ome')->model('products');
        $operationLogObj = &app::get('ome')->model('operation_log');
        $opinfo = kernel::single('ome_func')->get_system();
        
        $orderInfo = $orderObj->dump(array('order_id'=>$order_id,'shop_type'=>'taobao'),'order_bn,shop_type,shop_id,abnormal_status');
        if(!$orderInfo || ($orderInfo['abnormal_status'] & ome_preprocess_const::__HASGIFT_CODE) != ome_preprocess_const::__HASGIFT_CODE){
            return true;
        }
        $this->_apiGiftLog['order_bn'] = $orderInfo['order_bn'];
        $is_update = true;
        $local_tbgiftInfos = $tbgiftOrderItemsObj->getList('*',array('order_id'=>$order_id),0,-1);
        if($local_tbgiftInfos){
            $orderItemObj  = &app::get('ome')->model("order_items");
            $orderObjectObj  = &app::get('ome')->model("order_objects");

            foreach ($local_tbgiftInfos as $local_tbgiftInfo){
                if(empty(self::$_tb_gift_order_id )){
                    self::$_tb_gift_order_id  = $order_id;
                }

                $tbgiftBn = $this->getGiftBnFromTb($local_tbgiftInfo['outer_item_id'],$orderInfo['shop_id']);
                if($tbgiftBn){
                   
                        $tbgiftbn = $tbgiftBn;
                }else{
                    $is_update = false;
                    continue;
                }
                $is_bind = false;//这个回写标志,用来检测赠品是否是一个捆绑商品
                $productInfo = $this->findTheGift($tbgiftbn,$is_bind);
                if(!$productInfo){
                    $is_update = false;
                    continue;
                }
                #捆绑商品处理流程
                if($is_bind){
                    #捆绑商品库存不足判断
                    if(($productInfo['store'] < $local_tbgiftInfo['nums']) ||($productInfo['store'] == 0)){
                        return false;
                    }
                    $db = kernel::database();
                    $db->beginTransaction();
                    $tmp_obj = array(
                            'order_id' => $local_tbgiftInfo['order_id'],
                            'obj_type' => 'gift',
                            'shop_goods_id' => $local_tbgiftInfo['outer_item_id'],
                            'goods_id' => $productInfo['goods_id'],//捆绑商品的goods_id
                            'bn' => $productInfo['pkg_bn'],
                            'name' => $productInfo['pkg_name'],
                            'price' => 0.00,
                            'sale_price' => 0.00,
                            'pmt_price' => 0.00,
                            'amount' => 0.00,
                            'quantity' => $local_tbgiftInfo['nums'],//赠送捆绑商品的数量
                    );
                    $rs = $orderObjectObj->save($tmp_obj);
                    if($rs){
                        foreach($productInfo['item'] as $key=>$_productInfo){
                            $num = $local_tbgiftInfo['nums'] * $_productInfo['pkgnum'];#赠送捆绑商品的数量 * 单个货品捆绑数量
                            $tmp_items = array(
                                        'order_id' => $local_tbgiftInfo['order_id'],
                                        'obj_id' => $tmp_obj['obj_id'],
                                        'shop_goods_id' => $local_tbgiftInfo['outer_item_id'],
                                        'product_id' => $_productInfo['product_id'],
                                        'shop_product_id' => 0,
                                        'bn' => $_productInfo['bn'],
                                        'name' => $_productInfo['name'],
                                        'cost' => 0.00,
                                        'price' => 0.00,
                                        'amount' => 0.00,
                                        'sale_price'=> 0.00,
                                        'pmt_price' => 0.00,
                                        'quantity' => $num,
                                        'sendnum' => 0,
                                        'item_type' => 'gift',
                            );
                            if($orderItemObj->save($tmp_items)){
                                $productObj->chg_product_store_freeze($_productInfo['product_id'],$num,"+");
                            }else{
                                $is_update = false;
                                $db->rollBack(); break;
                            }
                        }                                                   
                     }else{
                        $is_update = false;
                        $db->rollBack();break;
                  } 
                  $db->commit();
                }else{
                    #普通商品处理流程
                    $tmp_obj = array(
                        'order_id' => $local_tbgiftInfo['order_id'],
                        'obj_type' => 'gift',
                        'shop_goods_id' => $local_tbgiftInfo['outer_item_id'],
                        'goods_id' => $productInfo['goods_id'],
                        'bn' => $productInfo['bn'],
                        'name' => $productInfo['name'],
                        'price' => 0.00,
                        'sale_price' => 0.00,
                        'pmt_price' => 0.00,
                        'amount' => 0.00,
                        'quantity' => $local_tbgiftInfo['nums'],
                    );
                    if($orderObjectObj->save($tmp_obj)){
                        $tmp_items = array(
                            'order_id' => $local_tbgiftInfo['order_id'],
                            'obj_id' => $tmp_obj['obj_id'],
                            'shop_goods_id' => $local_tbgiftInfo['outer_item_id'],
                            'product_id' => $productInfo['product_id'],
                            'shop_product_id' => 0,
                            'bn' => $productInfo['bn'],
                            'name' => $productInfo['name'],
                            'cost' => 0.00,
                            'price' => 0.00,
                            'amount' => 0.00,
                            'sale_price'=> 0.00,
                            'pmt_price' => 0.00,
                            'quantity' => $local_tbgiftInfo['nums'],
                            'sendnum' => 0,
                            'item_type' => 'gift',
        	            );
        	            if($orderItemObj->save($tmp_items)){
        	                $productObj->chg_product_store_freeze($productInfo['product_id'],$local_tbgiftInfo['nums'],"+");
        	            }else{
                            $is_update = false;
                            continue;
        	            }
                    }else{
                        $is_update = false;
                        continue;
                    }
                }
            }

            if($is_update){
                $status = $orderInfo['abnormal_status'] ^ ome_preprocess_const::__HASGIFT_CODE;
                $orderObj->update(array('abnormal_status'=>$status),array('order_id'=>$order_id));

                $operationLogObj->write_log('order_preprocess@ome',$order_id,'订单预处理优惠赠品信息',time(),$opinfo);
                return true;
            }else{
                $msg = '赠品无法匹配';
                $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);
                return false;
            }
        }else{
            $msg = '没有找到优惠赠品信息';
            $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);
            return false;
        }
    }

    /**
     * 根据赠品num_iid和shop_id获取赠品外部商家编码
     */
    private function getGiftBnFromTb($num_iid,$shop_id){
        $operationLogObj = &app::get('ome')->model('operation_log');
        $opinfo = kernel::single('ome_func')->get_system();
        if(!$num_iid || !$shop_id){
            return false;
        }

        if(isset(self::$__tb_gifts[$shop_id][$num_iid])){
            return self::$__tb_gifts[$shop_id][$num_iid];
        }

        $api_name ='store.item.get';
        $param = array(
            'iid' => $num_iid,
        );
        $timeout = 5;

        $result = kernel::single('ome_rpc_request')->call($api_name, $param, $shop_id, $timeout);
        if($result){
            if($result->rsp == 'succ'){
                $msg = '获取赠品成功';
                $this->_apiGiftLog['title'] = '请求淘宝赠品';
                $this->_apiLog['info'][] = $msg;
                $this->_apiGiftLog['msg_id'] = $result->msg_id;
                $this->_apiGiftLog['param'] = $param;
                $this->get_taobaoGift_log('success');
                $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
                $tmp = json_decode($result->data,true);
                self::$__tb_gifts[$shop_id][$num_iid] = $tmp['item']['outer_id'];
                //echo self::$__tb_gifts[$shop_id][$num_iid];exit;
                return self::$__tb_gifts[$shop_id][$num_iid];
            }else{
                $msg = '获取赠品失败';
                $this->_apiGiftLog['title'] = '请求淘宝赠品';
                $this->_apiGiftLog['info']['msg'] =  ($result->res == 'e00090')?'响应超时':$result->err_msg;
                $this->_apiGiftLog['msg_id'] = $result->msg_id;
                $this->_apiGiftLog['param'] = $param;
                $this->get_taobaoGift_log('fail');
                $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
                return false;
            }
        }else{
            $msg = '没有赠品信息';
            $this->_apiGiftLog['title'] = '请求淘宝赠品';
            $this->_apiGiftLog['info']['msg'] =  ($result->res == 'e00090')?'响应超时':$result->err_msg;
            $this->_apiGiftLog['msg_id'] = $result->msg_id;
            $this->_apiGiftLog['param'] = $param;
            $this->get_taobaoGift_log('fail');
            $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
            return false;
        }
    }

    /**
     * 根据淘宝赠品商家编码找到本地对应的赠品
     */
    private function findTheGift($bn,&$is_bind){
        if(!$bn){
            return false;
        }

        $tbgiftGoodsObj = &app::get('ome')->model('tbgift_goods');
        $tbgiftProductObj = &app::get('ome')->model('tbgift_product');
        $productObj = &app::get('ome')->model('products');
        $pkgProductObj = &app::get('omepkg')->model('pkg_product');

        if(isset(self::$__local_gifts[$bn])){
            $tmp_local_giftInfo = self::$__local_gifts[$bn];
        }else{
            $tmp_local_giftInfo = $tbgiftGoodsObj->dump(array('gift_bn'=>$bn),'*');
        }

        if($tmp_local_giftInfo){

            if(self::$__local_gifts[$bn]['products']){
                $tmp_local_productInfos = self::$__local_gifts[$bn]['products'];
            }else{
                $tmp_local_productInfos = $tbgiftProductObj->getList('*',array('goods_id'=>$tmp_local_giftInfo['goods_id']),0,-1);
            }

            if($tmp_local_productInfos){

                if(!isset(self::$__local_gifts[$bn])){
                    self::$__local_gifts[$bn] = $tmp_local_giftInfo;
                    self::$__local_gifts[$bn]['products'] = $tmp_local_productInfos;
                }
                #捆绑商品类型
                if($tmp_local_giftInfo['goods_type'] == 'bind'){
                    $is_bind = true;
                    if(count($tmp_local_productInfos) == 1){
                        $tmp_productInfo = $pkgProductObj->getList('*',array('goods_id'=>$tmp_local_productInfos[0]['product_id']),0,-1);
                        $pkg_goods['pkg_name'] = $tmp_local_productInfos[0]['name'];
                        $pkg_goods['pkg_bn'] = $tmp_local_productInfos[0]['bn'];
                        $pkg_goods['goods_id'] = $tmp_local_productInfos[0]['product_id'];
                        $pkg_goods['item'] = $tmp_productInfo;
                        #捆绑商品goods_id
                        $pkg_goods_id = $tmp_local_productInfos[0]['product_id'];
                        #获取捆绑商品的实际库存
                        $pkg_goods_store = $productObj->getPkgGoodsStore($pkg_goods_id);
                        $pkg_goods['store'] = $pkg_goods_store[0]['min_sotre'];
                        return $pkg_goods;
                    }else{
                        foreach($tmp_local_productInfos as $tmp_local_productInfo){
                            #所有捆绑商品的goods_id
                            $pkg_goods_id[] = $tmp_local_productInfo['product_id'];
                            $new_local_productInfos[$tmp_local_productInfo['product_id']] = $tmp_local_productInfo;
                        }
                        #获取所有捆绑商品的sku信息
                        $pkg_productInfo = $pkgProductObj->getList('*',array('goods_id'=>$pkg_goods_id),0,-1);
                        $all_pkg_goods = array();
                        foreach($pkg_productInfo as $v){
                            #获取所有捆绑商品的sku数据
                            $all_pkg_goods[$v['goods_id']][] = $v;
                        }
                        #获取所有捆绑商品的实际库存
                        $pkg_goods_store = $productObj->getPkgGoodsStore(implode(',',$pkg_goods_id));
                        $goods_id = $maxstore = 0;
                        foreach($pkg_goods_store as $_v){
                            if($_v['min_sotre'] >= $maxstore){
                                $maxstore = $_v['min_sotre'];
                                #找到实际库存最大的那个捆绑商品
                                $goods_id = $_v['goods_id'];
                            }
                        }
                        $pkg_goods['pkg_name'] = $new_local_productInfos[$goods_id]['name'];
                        $pkg_goods['pkg_bn'] = $new_local_productInfos[$goods_id]['bn'];
                        $pkg_goods['goods_id'] = $goods_id;
                        $pkg_goods['item'] = $all_pkg_goods[$goods_id];
                        $pkg_goods['store'] = $maxstore;
                        #返回实际库存最大的那个捆绑商品数据
                        return $pkg_goods;
                    }
                }else{#普通赠品类型
                            if(count($tmp_local_productInfos) == 1){
                                $tmp_productInfo = $productObj->dump($tmp_local_productInfos[0]['product_id'],'goods_id,product_id,bn,name');
                                return $tmp_productInfo;
                            }else{
                                foreach($tmp_local_productInfos as $tmp_local_productInfo){
                                    $tmp_productIds[] = $tmp_local_productInfo['product_id'];
                                }
            
                                $tmp_products = $productObj->getList('goods_id,product_id,bn,name,store,store_freeze',array('product_id'=>$tmp_productIds),0,-1);
                                if($tmp_products){
                                    $tmp_productInfo = 0;
                                    $maxstore = -1;
                                    $tmp = 0;
                                    foreach($tmp_products as $tmp_product){
                                        $tmp = ($tmp_product['store']-$tmp_product['store_freeze'])>0 ? ($tmp_product['store']-$tmp_product['store_freeze']) : 0;
                                        if($tmp > $maxstore){
                                            unset($tmp_product['store']);
                                            unset($tmp_product['store_freeze']);
                                            $tmp_productInfo = $tmp_product;
                                            $maxstore = $tmp;
                                        }
                                    }
                                    return $tmp_productInfo;
                                }else{
                                    return false;
                                }
                            }
                 }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function get_taobaoGift_log($status){
        $oApilog = &app::get('ome')->model('api_log');
        $log_id = $oApilog->gen_id();
        $params = $this->_apiGiftLog['param'];
        $params['msg_id'] = $this->_apiGiftLog['msg_id'];
        $oApilog->write_log($log_id,$this->_apiGiftLog['title'],'ome_preprocess_tbgift','rpc_request',array('store.item.get', $params, $callback),'','request',$status,implode('<hr/>',$this->_apiGiftLog['info']),'','api.store.gift.rule',$this->_apiGiftLog['order_bn']);
        return true;
    }
}
