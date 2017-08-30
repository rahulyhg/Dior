<?php
class ome_rpc_response_taoda_order
{
    var $status = array(
        'TRADE_ACTIVE' => 'active',
        'TRADE_CLOSED' => 'dead',
        'TRADE_FINISHED' => 'finish',
    );
    
    var $pay_status = array(
        'PAY_NO' => 0,
        'PAY_FINISH' => 1,
        'PAY_TO_MEDIUM' => 2,
        'PAY_PART' => 3,
        'REFUND_PART' => 4,
        'REFUND_ALL' => 5,
    );
    
    var $ship_status = array(
        'SHIP_NO' => 0,
        'SHIP_FINISH' => 1,
        'SHIP_PART' => 2,
        'RETRUN_PART' => 3,
        'RETRUN_ALL' => 4,
    );
    
    /**
     * 前端推送订单信息接口
     *
     * 
     */
    function docreate($result){

        $orders = json_decode($result['trades'],true);
        foreach ($orders['trade'] as $order){
            $this->create($order);
        }
    }
    function create($order_sdf){

        
        $orderObj = &app::get('ome')->model('orders');
        $membersObj = &app::get('ome')->model('members');
        $smemberObj = &app::get('ome')->model('shop_members');
        
        $shop_member_id = $order_sdf['user_id'];
        //$shop_id = $order_sdf['shop_id'];   //TODO:需要修正
        //$shop_info = app::get('ome')->model('shop')->dump($shop_id);
        //$shop_type = $shop_info['shop_type'];
        $sp = kernel::database()->selectrow("SELECT shop_id,shop_type FROM sdb_ome_shop");
        $shop_id = $sp['shop_id'];
        $shop_type = $sp['shop_type'];

        
        $sdf = array(
            'order_bn' => $order_sdf['tid'],
            'status' => $this->status[$order_sdf['status']],
            'pay_status' => $this->pay_status[$order_sdf['pay_status']],
            'ship_status' => $this->ship_status[$order_sdf['ship_status']],
            'shipping' => array(
                'shipping_name' => $order_sdf['shipping_name'],
                'cost_shipping' => $order_sdf['shipping_fee']?$order_sdf['shipping_fee']:0.000,
                'is_protect' => $order_sdf['is_protect']?$order_sdf['is_protect']:'false',
                'cost_protect' => $order_sdf['protect_fee']?$order_sdf['protect_fee']:0.000,
            ),
            'payinfo' => array(
                'pay_name' => $order_sdf['payment_name'],
                'cost_payment' => 0,
            ),
            'title' => $order_sdf['title'],
            'createtime' => strtotime($order_sdf['created']),
            'shop_id' => $shop_id,
            'shop_type' => $shop_type,
            'consignee' => array(
                'name' => $order_sdf['receiver_name'],
                'area' => $order_sdf['receiver_state']."/".$order_sdf['receiver_city']."/".$order_sdf['receiver_district'],
                'addr' => $order_sdf['receiver_address'],
                'zip' => $order_sdf['receiver_zip'],
                'telephone' => $order_sdf['receiver_phone'],
                'email' => $order_sdf['receiver_email'],
                'mobile' => $order_sdf['receiver_mobile'],
            ),
            'cost_item' => $order_sdf['total_goods_fee']?$order_sdf['total_goods_fee']:0.000,
            'is_tax' => $order_sdf['has_invoice']?$order_sdf['has_invoice']:'false',
            'cost_tax' => $order_sdf['invoice_fee']?$order_sdf['invoice_fee']:0.000,
            'tax_title' => $order_sdf['invoice_title'],
            'currency' => $order_sdf['currency'],
            'cur_rate' => $order_sdf['currency_rate'],
            'discount' => $order_sdf['discount_fee']?$order_sdf['discount_fee']:0.000,
            'total_amount' => $order_sdf['total_trade_fee']?$order_sdf['total_trade_fee']:0.000,
            'payed' => $order_sdf['payed_fee']?$order_sdf['payed_fee']:0.000,
            'mark_text' => $order_sdf['trade_memo'],
            'last_modified' => strtotime($order_sdf['modified']),
            'source' => 'taoda',
            'confirm' => 'Y',
            'process_status' => 'splited',
            'op_id' => 1,   //默认分派给超级管理员
            'dispatch_time' => time(),
        );
        
        //判断同bn订单是否已经存在
        if($tmp = $orderObj->dump(array('order_bn'=>$order_sdf['tid']))){
            $order_id = $tmp['order_id'];
            $sdf['order_id'] = $order_id;
        }else{
            $oGoods = &app::get('ome')->model('goods');
            $oProducts = &app::get('ome')->model('products');
            $i = 0;
            foreach($order_sdf['orders']['order'] as $v){
                //$goods_info = $oGoods->dump(array('bn'=>$v['bn'])); //TODO:确认要求会给bn
                $sdf['order_objects'][$i] = array(
                    'obj_type' => $v['order_type'],       //TODO:确认是否为这个参数
                    'obj_alias' => $v['order_typealias'],
                    'shop_goods_id' => $v['iid']?$v['iid']:0,
                    //'goods_id' => $goods_info['goods_id'],
                    //'bn' => $v['bn'],   //TODO:确认会给bn
                    'name' => $v['title'],
                    //'price' => $v['price']?$v['price']:0,  //TODO:需要提供单价，或者items里取
                    'weight' => $v['weight']?$v['weight']:0, //TODO:需要提供重量，也可以从items计算出
                    'quantity' => $v['items_num'],
                    'amount' => $v['total_order_fee'],
                    'score' => $v['score']?$v['score']:0,   //TODO:需要提供积分，或者从items里取
                );
                foreach($v['order_items']['item'] as $items){
                    $product_info = $oProducts->dump(array('bn'=>$items['bn']));
                    if(!isset($product_info['product_id']) || $product_info['product_id'] == "") trigger_error("中心不存在货品 ".$items['name']." ！",E_USER_ERROR);
                    $sdf['order_objects'][$i]['order_items'][] = array(
                        'shop_goods_id' => $items['iid']?$items['iid']:0,
                        'shop_product_id' => $items['sku_id']?$items['sku_id']:0,
                        'product_id' => $product_info['product_id'],
                        'item_type' => $items['item_type']?$items['item_type']:'product',
                        'bn' => $items['bn'],
                        'name' => $items['name'],
                        'quantity' => $items['num']?$items['num']:1,
                        'sendnum' => $items['sendnum']?$items['sendnum']:0,
                        'amount' => $items['total_item_fee']?$items['total_item_fee']:0.000,
                        'price' => $items['price']?$items['price']:0.000,
                        'weight' => $items['weight'],
                        'score' => $items['score'],
                    );
                    //更新商品的冻结库存
                    $oProducts->chg_product_store_freeze($product_info['product_id'],(intval($items['num'])-intval($items['sendnum'])),"+");
                }
                $i ++;
            }
            
            
            //通过member_id和shop_id查找是否已经存在member，不存在则创建一个
            //如果店铺类型为淘宝、有啊、拍拍的话，查找外部会员id与当前外部id相等的
            if($shop_member_id){
                $smember = array();
                if (in_array($shop_type,array('taobao','youa','paipai'))){
                    $smember = $smemberObj->dump(array('shop_member_id'=>$shop_member_id));
                }else{
                    $smember = $smemberObj->dump(array('shop_member_id'=>$shop_member_id,'shop_id'=>$sdf['shop_id']));
                }
                if ($smember){
                    $sdf['member_id'] = $smember['member_id'];
                }else{
                    //淘宝店铺新用户购买，新建用户，插入shop_members信息
                    $memberdata = array('account'=>array('uname'=>$order_sdf['user_name']),'contact'=>array('name'=>$order_sdf['user_name']));
                    $membersObj->save($memberdata);
                    $smemberinfo = array();
                    $smemberinfo['shop_id'] = $sdf['shop_id'];
                    $smemberinfo['shop_member_id'] = $shop_member_id;
                    $smemberinfo['member_id'] = $memberdata['member_id'];
                    $smemberObj->save($smemberinfo);
                    //order中member_id
                    $sdf['member_id'] = $memberdata['member_id'];
                }
            }else if($order_sdf['user_name']){
                if($res=kernel::database()->selectrow("SELECT member_id FROM sdb_ome_members WHERE uname='".$order_sdf['user_name']."'")){
                    $sdf['member_id'] = $res['member_id'];
                }else{
                    $memberdata = array('account'=>array('uname'=>$order_sdf['user_name']),'contact'=>array('name'=>$order_sdf['user_name']));
                    $membersObj->save($memberdata);
                    $sdf['member_id'] = $memberdata['member_id'];
                }
            }
        }
        $sdf['order_limit_time'] = time() + 60*(app::get('ome')->getConf('ome.order.failtime'));
        
        if($orderObj->save($sdf)){
            //nothing
        }else{
            trigger_error("中心订单保存失败！",E_USER_ERROR);
        }
        //保存
    }

    function update($order_sdf){
        //TODO:确认订单修改流程
    }
    
    function get_orders($result){
        /*
         * 
         * $where 定义
         * array(
         *    'order_bn' => 'T123456789'/array('T123456789','T123456790'),
         *    'status' => 'active',
         *    'process_status' => array('unconfirmed','confirmed')/'unconfirmed',
         *    'pay_status' => array('2','1')/'1',
         *    'createtime' => array('start'=>'123','end'=>'321')/'123',
         *    'limit' => 20/array(10,30),
         * )
         */
        $where = $result->get_where();
        $data  = $this->get_order($where);
        return $data;
    }
    
    
    function get_order($filter){
        $oObj = &app::get('ome')->model('orders');
        $filter = array();
        $filter['limit'] = 5;
        $filter['user_name'] = 'admin';
        $filter['user_password'] = '21232f297a57a5a743894a0e4a801fc3';
        if (!empty($filter)){
            $where = '1';
            //筛选订单编号
            if (!empty($filter['order_bn'])){
                if (is_array($filter['order_bn'])){
                    $str = "'";
                    $str .= implode("', '", $filter['order_bn']);
                    $str .= "'";
                    $where .= " AND order_bn IN ($str) ";
                }else {
                    $str = $filter['order_bn'];
                    $where .= " AND order_bn = '$str' ";
                }
            }
            $pay_status = array(
                '0' => 'PAY_NO',
                '1' => 'PAY_FINISH',
                '2' => 'PAY_TO_MEDIUM',
                '3' => 'PAY_PART',
                '4' => 'REFUND_PART',
                '5' => 'REFUND_ALL',
            );
            $pay_status_back = array(
                'PAY_NO' => '0',
                'PAY_FINISH' => '1',
                'PAY_TO_MEDIUM' => '2',
                'PAY_PART' => '3',
                'REFUND_PART' => '4',
                'REFUND_ALL' => '5',
            );
            $ship_status = array(
                '0' => 'SHIP_NO',
                '1' => 'SHIP_FINISH',
                '2' => 'SHIP_PART',
                '3' => 'RETRUN_PART',
                '4' => 'RETRUN_ALL',
            );
            $ship_status_back = array(
                'SHIP_NO' => '0',
                'SHIP_FINISH' => '1',
                'SHIP_PART' => '2',
                'RETRUN_PART' => '3',
                'RETRUN_ALL' => '4',
            );
            //筛选订单状态
            if (!empty($filter['pay_status'])){
                $str = $pay_status_back[$filter['pay_status']];
                $where .= " AND pay_status = '$str' ";
            }else {
                $where .= " AND pay_status = '1' ";
            }
            
            if (!empty($filter['ship_status'])){
                $str = $ship_status_back[$filter['pay_status']];
                $where .= " AND ship_status = '$str' ";                
            }
            /*//筛选订单处理状态
            if (!empty($filter['process_status'])){
                if (is_array($filter['process_status'])){
                    $str = implode(',', $filter['process_status']);
                    $where .= " AND process_status IN ($str)";
                }else {
                    $str = $filter['process_status'];
                    $where .= " AND process_status = '$str'";
                }
            }*/
            //筛选订单下单时间
            if (!empty($filter['time_from']) && !empty($filter['time_to'])){
                $from   = strtotime($filter['time_from']);
                $to     = strtotime($filter['time_to'])+24*60*60-1;
                $where .= " AND (createtime >= $from AND createtime <= $to) ";
            }
            //筛选订单页数
            if (!empty($filter['page'])){
                $page = $filter['page'];
            }else {
                $page = 1;
            }
            //筛选订单单个页数数量
            if (!empty($filter['limit'])){
                $limit = $filter['limit'];
            }else {
                $limit = 20;
            }
            
            //筛选管理员
            $name = $filter['user_name'];
            $password = $filter['user_password'];
            $sql = "SELECT user_id,super FROM sdb_pam_account pa JOIN sdb_desktop_users du ON pa.account_id=du.user_id WHERE pa.login_name='$name' AND pa.login_password='$password'";
            $user = $oObj->db->selectrow($sql);
            if ($user['super'] != '1'){
                $op_id = $user['user_id'];
                $where .= " AND op_id = '$op_id' ";
            }
            $ooObj = &app::get('ome')->model('order_objects');
            $oiObj = &app::get('ome')->model('order_items');
            
            $max   = $page * $limit;
            $min   = ($page-1) * $limit;
            $sql   = "SELECT * FROM sdb_ome_orders WHERE $where LIMIT $min,$max ";
            echo $sql;
            $data  = $oObj->db->select($sql);
            $i     = 0;
            foreach ($data as $item){
                $oo = $ooObj->getList('*',array('order_id'=>$item['order_id']),0,-1);
                $area = explode('/', $item['ship_area']);
                $order_sdf[$i] = array(
                    'tid'                       => $item['order_bn'],
                    'title'                     => $item['tostr'],
                    'created'                   => date("Y-m-d H:i:s",$item['createtime']),
                    'modified'                  => date("Y-m-d H:i:s",$item['last_modified']),
                    'status'                    => $item['status'],
                    'pay_status'                => $item['pay_status'],
                    'ship_status'               => $item['ship_status'],
                    'has_invoice'               => $item['is_tax'],
                    'invoice_title'             => $item['tac_company'],
                    'invoice_fee'               => $item['cost_tac'],
                    'total_goods_fee'           => $item['total_amount'],
                    'total_trade_fee'           => $item['final_amount'],
                    'discount_fee'              => $item['discount'],
                    'payed_fee'                 => $item['payed'],
                    'currency'                  => $item['currency'],
                    'currency_rate'             => $item['cur_rate'],
                    'total_currency_fee'        => $item['order_id'],//TODO当前货别订单总额 
                    'buyer_obtain_point_fee'    => $item['score_g'],
                    'point_fee'                 => $item['score_u'],
                    'shipping_id'               => $item['shipping'],
                    'shipping_name'             => $item['shipping'],
                    'shipping_fee'              => $item['cost_freight'],
                    'is_protect'                => $item['is_protect'],
                    'protect_fee'               => $item['cost_protect'],
                    'payment_id'                => $item['order_id'],//TODO支付方式ID 
                    'payment_name'              => $item['payment'],
                    'pay_time'                  => $item['order_id'],//TODO  支付时间。格式:yyyy-MM-dd HH:mm:ss 
                    'receiver_name'             => $item['ship_name'],
                    'receiver_email'            => $item['ship_email'],
                    'receiver_state'            => $area[0],
                    'receiver_city'             => $area[1],
                    'receiver_district'         => $area[2],
                    'receiver_address'          => $item['ship_addr'],
                    'receiver_zip'              => $item['ship_zip'],
                    'receiver_mobile'           => $item['ship_mobile'],
                    'receiver_phone'            => $item['ship_tel'],
                    'member_id'                 => $item['member_id'],
                    'trade_memo'                => $item['mark_text'],
                    'orders_number'             => sizeof($oo),
                );
                
                $n = 0;
                foreach ($oo as $ooi){
                    $order_sdf[$i]['orders'][$n] = array(
                        'oid'               => $item['order_bn'],
                        'iid'               => $ooi['shop_goods_id'],
                        'title'             => $ooi['name'],
                        'items_num'         => $ooi['amount'],
                        'order_type'        => $ooi['obj_type'],
                        'order_typealias'   => $ooi['obj_alias'],
                        'total_order_fee'   => $ooi['amount'],//总计
                    );
                    
                    $oi = $oiObj->getList('*',array('obj_id'=>$ooi['obj_id']),0,-1);
                    foreach ($oi as $oii){                        
                       $order_sdf[$i]['orders'][$n]['order_items'][] = array(
                           
                           'sku_id'            => $oii['shop_product_id'],
                           'iid'               => $ooi['shop_goods_id'],
                           'bn'                => $oii['bn'],
                           'name'              => $oii['name'],
                           'weight'            => $oii['weight'],
                           'score'             => $oii['score'],//积分
                           'price'             => $oii['price'],
                           'num'               => $oii['nums'],
                           'sendnum'           => $oii['sendnum'],
                           'total_item_fee'    => $oii['amount'],//总计
                           'item_type'         => $oii['item_type'],
                       );
                    }
                    $n++;
                }
                $i++;
            }
            echo "<pre>";
            print_r($order_sdf);
            return $order_sdf;
        }
        
        return false;
    }

    function to_delivery($result){
        $data = json_decode($result['data'],true);

        $oObj   = &app::get('ome')->model('orders');
        $oiObj  = &app::get('ome')->model('order_items');
        $dObj   = &app::get('ome')->model('delivery');
        $bObj   = &app::get('ome')->model('branch');
        $dcObj  = &app::get('ome')->model('dly_corp');
        $db     = kernel::database();


        $flag = array();
        $error = false;
        foreach ($data as $single){
            $bns = explode(",",$single['order_bns']);
            foreach ($bns as $check){
                $o = $oObj->dump(array('order_bn'=>$check));
                $delivery = $db->select("SELECT delivery_id FROM sdb_ome_delivery_order WHERE order_id=".$o['order_id']);
                foreach($delivery as $v){
                    $delivery_info = $dObj->dump($v['delivery_id']);
                    if($delivery_info['process'] == 'true' || $delivery_info['status'] == 'succ'){
                        $error = true;
                        $flag['deliveried_order'][] = $check;
                    }
                }
                
            }
            
            //检查物流单号是否重复
            if($db->selectrow("SELECT delivery_id FROM sdb_ome_delivery WHERE logi_no='".$single['logi']['logi_no']."' AND delivery_bn<>'".$single['delivery_id']."'")){
                $error = true;
                $flag['duplicate_logi_no'][] = $single['logi']['logi_no'];
            }
        }
        if ($error){
            trigger_error(json_encode($flag),E_USER_ERROR);
        }else{
            //说明订单的发货单未发货，则删除发货单，重新生成，以新的为准
            foreach($data as $v){
                $t_dly = $dObj->dump(array("delivery_bn"=>$v['delivery_id']));
                if($t_dly){
                    $t_delivery_id = $t_dly['delivery_id'];
                    if($t_child_dly = $db->select("SELECT delivery_id FROM sdb_ome_delivery WHERE parent_id=".$t_delivery_id)){
                        foreach($t_child_dly as $v){
                            $dObj->delete(array('delivery_id'=>$v['delivery_id']));
                            $dObj->deleteDeliveryOrderByDeliveryId($v['delivery_id']);
                            //$dObj->deleteDeliveryItemsPosByDeliveryId(array($v['delivery_id']));
                            $dObj->deleteDeliveryItemsByDeliveryId($v['delivery_id']);
                        }
                    }
                    $dObj->delete(array('delivery_id'=>$t_delivery_id));
                    $dObj->deleteDeliveryOrderByDeliveryId($t_delivery_id);
                    //$dObj->deleteDeliveryItemsPosByDeliveryId(array($t_delivery_id));
                    $dObj->deleteDeliveryItemsByDeliveryId($t_delivery_id);                
                }
            }
        }
        foreach ($data as $item){
            $delivery_id = $item['delivery_id'];
            $orders      = explode(",",$item['order_bns']);
            $logi_no     = $item['logi']['logi_no'];
            $type        = $item['logi']['logi_code'];
            $logi_name   = $item['logi']['logi_name'];
            $shop_id     = $item['shop_id'];
            
            $ship['name']       = $item['shipping_info']['receiver_name'];
            $ship['area']       = $item['shipping_info']['receiver_state'].'/'.$item['shipping_info']['receiver_city'].'/'.$item['shipping_info']['receiver_district'];
            $ship['addr']       = $item['shipping_info']['receiver_address'];
            $ship['zip']        = $item['shipping_info']['receiver_zip'];
            $ship['mobile']     = $item['shipping_info']['receiver_mobile'];
            $ship['telephone']  = $item['shipping_info']['receiver_phone'];
            $ship['email']      = $item['shipping_info']['receiver_email'];
            
            $corp = $dcObj->dump(array('type'=>$type));
            if (!$corp){
                $corp = $dcObj->dump(array('name'=>$logi_name));
            }

            
            if (sizeof($orders) > 1){
                $is_once = false;
                $dly_ids = array();
                $dlyIds  = array();
            }else {
                $is_once = true;
            }
            foreach ($orders as $o){
                $dly = array();
                $order = $oObj->dump(array('order_bn'=>$o));
                //print_r($order);
                if ($is_once){
                    $dly['delivery_bn']    = $delivery_id;
                }else {
                    $dly['delivery_bn'] = $dObj->gen_id();
                    $dly_ids[]          = $dly['delivery_bn'];
                }
                $branch = $bObj->getList('branch_id');
                $order_items = $oiObj->getList('*',array('order_id'=>$order['order_id']),0,-1);
                $dly['logi_id']     = $corp['corp_id'];//
                $dly['logi_name']   = $corp['name'];//
                $dly['branch_id']   = $branch[0]['branch_id'];
                $dly['is_protect']  = $order['is_protect']?'true':'false';
                $dly['is_cod']      = $order['is_cod']?'true':'false';
                $dly['delivery']    = $order['shipping']['shipping_name'];
                $dly['type']        = 'normal';
                
                foreach ($order_items as $k => $oi){
                    $dly['delivery_items'][$k]['product_id']        = $oi['product_id'];
                    $dly['delivery_items'][$k]['bn']                = $oi['bn'];
                    $dly['delivery_items'][$k]['product_name']      = $oi['name'];
                    $dly['delivery_items'][$k]['number']            = $oi['nums'];
                    $dly['delivery_items'][$k]['shop_product_id']   = $oi['shop_product_id'];
                }
                
                $oo['order_id']         = $order['order_id'];
                $oo['confirm']          = 'Y';
                $oo['print_finish']     = 'true';
                $oo['process_status']   = 'splited';
                
                $oObj->save($oo);
                $dObj->addDelivery($order['order_id'],$dly,$ship);
            }
            if (!$is_once){
                $dly['delivery_bn']     = $delivery_id;
                $dly['logi_no']         = $logi_no;
                $dly['logi_id']         = $corp['corp_id'];
                $dly['logi_name']       = $corp['name'];
                $dly['status']          = 'progress';
                $dly['stock_status']    = 'true';
                $dly['deliv_status']    = 'true';
                $dly['expre_status']    = 'true';
                
                foreach ($dly_ids as $i){
                    $dd = $dObj->dump(array('delivery_bn'=>$i),'delivery_id');
                    $dlyIds[] = $dd['delivery_id'];
                }
                $dObj->merge($dlyIds, $dly);
            }else {
                $dly['logi_no']         = $logi_no;
                $dly['status']          = 'progress';
                $dly['stock_status']    = 'true';
                $dly['deliv_status']    = 'true';
                $dly['expre_status']    = 'true';
                
                $dObj->update($dly, array('delivery_bn'=>$delivery_id));
            }
        }
    }
}
?>