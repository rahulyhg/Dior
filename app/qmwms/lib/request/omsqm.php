<?php

/**
 * 奇门标准化WMS流程（OMS->WMS）
 * 处理奇门接口返回信息，记录日志等
 */
class qmwms_request_omsqm extends qmwms_request_qimen{

    public function __construct($app){
        $this->log_mdl = app::get('qmwms')->model('qmrequest_log');
    }

    //发货单创建
    public function deliveryOrderCreate($order_id){
        $method = 'deliveryorder.create';
        $msg = '发货单创建';
        $res = $this->_deliveryOrderCreate($order_id);
        $body = $res['body'];
        $order_bn = $res['order_bn'];

        //记录ERP请求日志
        $data = $this->pre_params($order_bn,$order_id,$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$order_id,'deliveryOrderCreate',null);
            if(empty($response)){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据创建失败';
            }
            $this->writeLog($res_data,$insert_id);
            if($res_data['status']!='success'){
                //发送报警邮件
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Fresh-PROD】ByPass订单#'.$order_bn.'发货创建失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>{$res_data['res_msg']}<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
    }

    //退货入库单创建
    public function returnOrderCreate($delivery_id,$reship_id){
        $method = 'returnorder.create';
        $msg = '退货入库单创建';
        //组织请求数据体
        $res  = $this->_returnOrderCreate($reship_id);
        $body = $res['body'];
        $reship_bn = $res['reship_bn'];
        //return $body;
        //记录ERP请求日志
        $data = $this->pre_params($reship_bn,$reship_id,$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$reship_id,'returnOrderCreate',null);
            if(empty($response)){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据创建失败';
            }
            $this->writeLog($res_data,$insert_id);
            if($res_data['status']!='success'){
                //发送报警邮件
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Fresh-PROD】ByPass退单#'.$reship_bn.'退货创建失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>{$res_data['res_msg']}<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }

    }

    //单据取消
    public function orderCancel($delivery_id,$memo){
        $method = 'order.cancel';
        $msg = '单据取消';
        $res = $this->_orderCancel($delivery_id,$memo);
        $body  = $res['body'];
        $dj_bn = $res['order_bn'];
        //记录ERP请求日志
        $data = $this->pre_params($dj_bn,$delivery_id,$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$delivery_id,'orderCancel',$memo);
            if(empty($response)){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据取消失败';
            }
            $res_data['param_sx'] = $memo;
            $this->writeLog($res_data,$insert_id);
            //发送报警邮件
            if($res_data['status'] != 'success'){
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Fresh-PROD】ByPass订单#'.$dj_bn.'单据取消失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys   = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>{$res_data['res_msg']}<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
        return $res_data;
    }

    //库存查询
    public function inventoryQuery($bn=array(),$offset,$limit){
        $method = 'inventory.query';
        $msg = '库存查询';
        $body = $this->_inventoryQuery($bn);
        //记录ERP请求日志
        $data = $this->pre_params('','',$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,null,'inventoryQuery',null,$offset,$limit);
            if(empty($response)){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '库存更新失败';
            }
            $this->writeLog($res_data,$insert_id);
            //发送报警邮件
            if($res_data['status'] != 'success'){
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Fresh-PROD】ByPass库存更新失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys   = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>{$res_data['res_msg']}<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
    }

    //记录接口请求和返回信息日志
    public function writeLog($data,$log_id=null){
        if(!$log_id){
            $insert_id = $this->log_mdl->insert($data);

        }else{
            $this->log_mdl->update($data,array('log_id'=>$log_id));
        }
        return $insert_id;
    }

    //请求信息处理
    protected function pre_params($bn,$id,$method,$msg,$body){
        $data = array(
            'original_bn'=> $bn,
            'original_id'=> $id,
            'task_name'=>$method,
            'original_params'=>$body,
            'log_type'=>'向奇门发起请求',
            'msg'=>$msg,
            'createtime'=>time()
        );
        return $data;
    }

    //返回信息处理
    public function res_params($response,$dj_id,$method,$memo,$offset=null,$limit=null){
        //把返回的xml解析成数组
        $_response = kernel::single('qmwms_response_qmoms')->xmlToArray($response);
        //根据不同接口类型进行不同的处理
        if($_response['flag'] == 'success'){
            switch($method){
                case 'deliveryOrderCreate':
                    $ax_order_bn = $_response['deliveryOrderId'];
                    $sql = sprintf("update sdb_ome_orders set ax_order_bn = '%s' where order_id = %s ",$ax_order_bn,$dj_id);
                    if(!empty($ax_order_bn))kernel::database()->exec($sql);
                    break;
                case 'returnOrderCreate':
                    $ordersModel   = app::get('ome')->model('orders');
                    $reshipModel   = app::get('ome')->model('reship');
                    $orderId    = $reshipModel->dump(array('reship_id'=>$dj_id),'order_id');
                    $orderData      = $ordersModel->dump(array('order_id'=>$orderId['order_id']),'order_bn');
                    $order_bn = $orderData['order_bn'];
                    //状态更新到dw
                    kernel::single('omedw_dw_to_order')->send_return(array($order_bn));
                    break;
                case 'orderCancel':
                    //$this->do_cancel($dj_id,$memo);
                    break;
                case 'inventoryQuery':
                    //更新系统库存
                    $this->update_store_all($_response,$offset,$limit);
                    break;
                default:
                    break;

            }
        }

        $data = array(
            'response' => $response,
            'status' =>$_response['flag'],
            'res_msg' =>$_response['message'],
        );
        return $data;
    }

    /**
     * @param $order_id
     * 订单取消
     */
    public function do_cancel($delivery_id,$memo){
        $orderId = app::get('ome')->model('delivery_order')->getList('order_id',array('delivery_id'=>$delivery_id));
        $order_id = $orderId[0]['order_id'];
        $oOrder = &app::get('ome')->model('orders');
        $orderdata = $oOrder->dump($order_id);
        $order_bn = $orderdata['order_bn'];
        $memo = "订单被取消 ".$memo;
        $mod = 'sync';
        $oShop = &app::get('ome')->model('shop');
        $c2c_shop_list = ome_shop_type::shop_list();
        $shop_detail = $oShop->dump(array('shop_id'=>$orderdata['shop_id']),'node_id,node_type');
        if(!$shop_detail['node_id'] || in_array($shop_detail['node_type'],$c2c_shop_list) || $orderdata['source'] == 'local'){
            $mod = 'async';
        }
        $sync_rs = $oOrder->cancel($order_id,$memo,true,$mod);
        if($sync_rs['rsp'] == 'success') {
            //取消订单发票记录
            if(app::get('invoice')->is_installed()) {
                $Invoice       = &app::get('invoice')->model('order');
                $Invoice->delete_order($order_id);
            }
            //状态更新到dw
            if(app::get('omedw')->is_installed()){
                kernel::single('omedw_dw_to_order')->send_cancel(array($order_bn));
            }
        }else{
            //error_log(var_export('订单'.$order_bn.'取消失败,原因是:'.$sync_rs['msg'],true),3,__FILE__.'fail.txt');
            error_log(date('Y-m-d H:i:s').'订单'.$order_bn.'取消失败,原因是:'."\r\n".var_export($sync_rs['msg'],true)."\r\n", 3, __FILE__.'fail.txt');
        }

    }

    /**
     * @param $res
     * 读取WMS库存 同步到Magento
     */
    public function update_store_all($res,$offset,$limit){
        $product_mdl = app::get('ome')->model('products');
        $branch_product = app::get('ome')->model('branch_product');
        $all_product = $product_mdl->db->select("select p.product_id,p.bn from sdb_ome_products as p left join sdb_ome_goods as g on g.goods_id=p.goods_id where g.is_presell='false' order by p.product_id ASC limit $offset,$limit ");

        if(!$res['items']['item'][0]){
            $res['items']['item'] = array($res['items']['item']);
        }
        $wms_all_products = array();
        foreach($res['items']['item'] as $items){
            $product_id = $product_mdl->getList('product_id',array('bn'=>$items['itemCode']));
            if(empty($product_id[0])){
                continue;
            }
            $wms_all_products[$items['itemCode']] = $items;
        }
        $all_store = array();
        foreach($all_product as $product){
            $bn = $product['bn'];
            if($wms_all_products[$bn]){
                $wms_product = $wms_all_products[$bn];
                $store_freeze = $branch_product->getList('store_freeze,safe_store',array('product_id'=>$product['product_id'],'branch_id'=>1));
                $store = $wms_product['quantity']+$store_freeze[0]['store_freeze'];
                $re = $branch_product->change_store(1,$product['product_id'],$store);
                if($re){
                    $hasUser = kernel::single('omeftp_auto_update_product')->getHasUseStore($product['bn']);
                    $magentoStore = $wms_product['quantity']+$store_freeze[0]['store_freeze']-$hasUser;
                    if($magentoStore<0){
                        $magentoStore = 0;
                    }
                }
                $ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
                $ax_safe_store = $ax_setting['ax_safe_store']?$ax_setting['ax_safe_store']:15;

                if($store_freeze[0]['safe_store']>0){
                    if(intval($wms_product['quantity'])>$store_freeze[0]['safe_store']){
                        $all_store[] = array(
                            'bn'=>$bn,
                            'store'=>$magentoStore,
                        );
                    }
                }else{
                    if(intval($wms_product['quantity'])>$ax_safe_store){
                        $all_store[] = array(
                            'bn'=>$bn,
                            'store'=>$magentoStore,
                        );
                    }
                }

            }else{
                $store_freeze = $branch_product->getList('store_freeze',array('product_id'=>$product['product_id'],'branch_id'=>1));
                $store = $store_freeze[0]['store_freeze']?$store_freeze[0]['store_freeze']:0;
                $branch_product->change_store(1,$product['product_id'],$store);
                $magentoStore = $store;
                $all_store[] = array(
                    'bn'=>$bn,
                    'store'=>$magentoStore,
                );
            }
        }
        //状态更新到dw
        kernel::single('omedw_dw_to_product')->send_store($all_store);
    }

















































}