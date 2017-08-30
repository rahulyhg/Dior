<?php
/**
 * ome模板处理
 * 此模块用于ome的模板service处理
 * @author chris.zhang
 * @name ome.delivery.template
 * @package ome_delivery_template
 * @copyright www.shopex.cn 2010.11.24
 *
 */
class wms_delivery_template{

    protected $elements = array(
        'ship_name'   => '收货人-姓名',
        'ship_area_0' => '收货人-地区1级',
        'ship_area_1' => '收货人-地区2级',
        'ship_area_2' => '收货人-地区3级',
        'ship_addr'   => '收货人-地址',
        'ship_addr_mark'  => '收货人-地址-备注',
        'ship_detailaddr' => '收货人-详细地址',
        'ship_detailaddr_mark' => '收货人-详细地址-备注',
        'delivery_bn' => '收货人-发货单号',
        'logi_no' => '快递单号',
        'ship_tel'    => '收货人-电话',
        'ship_mobile' => '收货人-手机',
        'ship_zip'    => '收货人-邮编',

        'dly_name'    => '发货人-姓名',
        'dly_area_0'  => '发货人-地区1级',
        'dly_area_1'  => '发货人-地区2级',
        'dly_area_2'  => '发货人-地区3级',
        'dly_address' => '发货人-地址',
        'dly_detailaddr' => '发货人-详细地址',
        'dly_tel'     => '发货人-电话',
        'dly_mobile'  => '发货人-手机',
        'dly_zip'     => '发货人-邮编',

        'date_y'      => '当日日期-年',
        'date_m'      => '当日日期-月',
        'date_d'      => '当日日期-日',
        'date_h'      => '当日日期-时',
        'date_i'      => '当日日期-分',
        'date_s'      => '当日日期-秒',

        'order_bn'    => '订单-订单号',
        'order_count' => '快递单-物品数量',
        'delyvery_memo' => '快递单-备注',
        'delivery_order_amount' => '快递单-总价',
        'delivery_order_amount_d' => '快递单-总价(大写)',
        'delivery_receivable' => '快递单-应收款',
            
        'delivery_receivable_d' => '快递单-应收款(大写)',
        'order_memo'  => '卖家备注',
        'order_custom' => '客户留言',
        'shop_name'   => '网店名称',

        'bn_spec_num_n' => '商家编码+规格+数量(不换行)',
        'bn_spec_num_y' => '商家编码+规格+数量(换行)',
            
         'goodsbn_spec_num_n' => '商品编码+规格+数量(不换行)',
         'goodsbn_spec_num_y' => '商品编码+规格+数量(换行)',
            
            
            

        //12.15需求，TODO 暂时注释
        'member_uname' => '会员名',
        'bn_amount_n' => '货号+数量(不换行)',
        'name_amount_n' => '货品名+数量(不换行)',
        'bn_name_amount_n' => '货号+货品名+数量(不换行)',

        'bn_amount' => '货号+数量',
        'name_amount' => '货品名+数量',
        'bn_name_amount' => '货号+货品名+数量',

        'bn_amount_pos' => '货号+数量+货位',
        'name_amount_pos' => '货品名+数量+货位',
        'bn_name_amount_pos' => '货号+货品名+数量+货位',

        'print_no' => '打印批次号',

        'tick'        => '对号 - √',
        'text'        => '自定义内容',
        'name_spec_amount' => '货品名+规格+数量',
        'bn_name_spec_amount' => '货号+货品名+规格+数量(不换行)',
        'bn_name_spec_amount_y' => '货号+货品名+规格+数量(换行)',
        'new_bn_name_amount' => '{商品名称+数量}不换行',
        //货号+规格+数量
        'bn_spec_num'=>'货号+规格+数量',

        'total_product_weight_g'=>'货品重量 单位：g',
        'total_product_weight_kg'=>'货品重量 单位：kg',
        'bn_productname_spec_num_pos_n'=>'货号 货品名称 规格 数量 货位(换行)',
        'bn_productname_spec_num_pos'=>'货号 货品名称 规格 数量 货位(不换行)',
        'goods_bn'=>'商家编码',
        'pkgbn_num_n'=>'捆绑商品货号+数量(换行)',
        'sfcity_code'=>'顺丰城市代码',
        'mailno_position' => '面单大头笔',
        'mailno_position_no' => '面单大头笔编码',
        'package_wdjc' => '集包地',
        'cloud_stack_position' => '云栈大头笔', 
    );

    /**
     * default elements
     * 默认配置列表
     * @return array();
     */
    public function defaultElements(){
        $printTagObj = app::get('wms')->model('print_tag');
        $rows = $printTagObj->getList('*');
        foreach($rows as $row){
            if($row['tag_id']>0){
                $key = 'print_tag_'.$row['tag_id'];
                $this->elements[$key] = '大头笔-'.$row['name'];
            }
        }
        return $this->elements;
    }

    /**
     * process default print content
     * 处理快递单打印项的对应内容
     * @param array $value_list
     * @return string
     */
    public function processElementContent($value_list)
    {
        #[拆单]订单是否拆单 ExBOY
        $oDelivery   = &app::get('ome')->model('delivery');
        $is_split    = false;
        
        //12.15需求 ，TODO 暂时注释
        $order_Objects = &app::get('ome')->model('order_objects');
        $orderObj = &app::get('ome')->model('orders');
        $orderItemsObj = &app::get('ome')->model('order_items');
        if($value_list['delivery_order']) {
            $orderIds = array_keys($value_list['delivery_order']);

            $tbfxitemObj = &app::get('ome')->model('tbfx_order_items');
            $orders = $orderObj->getList('cost_freight,order_id, process_status, ship_status, total_amount,shop_type,order_source',array('order_id'=>$orderIds));

            $delivery_amount = $delivery_receivable = 0;

            foreach ($orders as $order) {
                if($order['order_source'] == 'tbdx' && $order['shop_type'] == 'taobao'){
                    $itemdata = $tbfxitemObj->dump(array('order_id'=>$order['order_id']),'SUM(buyer_payment) AS total_buyer_payment');
                    $delivery_amount += ($order['cost_freight']+$itemdata['total_buyer_payment']);
                }else{
                    $delivery_amount += $order['total_amount'];
                }
                
                #[拆单]订单是否为部分拆分OR部分发货
                if($order['process_status'] == 'splitting' || $order['ship_status'] == '2')
                {
                    $is_split    = true;
                }
                
                #[拆单]订单是否有多个发货单
                if($is_split == false)
                {
                    $is_split    = $oDelivery->validDeiveryByOrderId($order['order_id']);
                }
            }
            

            $orderExtendObj = &app::get('ome')->model('order_extend');
            $orderExtends = $orderExtendObj->getList('receivable',array('order_id'=>$orderIds));
            foreach ($orderExtends as $extend) {
                $delivery_receivable += $extend['receivable'];
            }

            $data['delivery_order_amount'] = number_format($delivery_amount, 2, '.', ' ');
            $data['delivery_order_amount_d'] = $this->financeNum(number_format($delivery_amount, 2, '.', ''));
            $data['delivery_receivable'] = number_format($delivery_receivable, 2, '.', ' ');
            $data['delivery_receivable_d'] = $this->financeNum(number_format($delivery_receivable, 2, '.', ''));
        }
        
        #商品明细
        $orderItemsInfo = array();
        foreach ($orderIds as $orderId) {
            $tmpOrderItemsInfo = $orderItemsObj->getList('*', array('order_id' => $orderId));
            foreach ($tmpOrderItemsInfo as $tmpValue) {
                $orderItemsInfo[$orderId][$tmpValue['obj_id']]['delete'] = $tmpValue['delete'];
            }
        }
        
        #[拆单]重新获取发货单对应商品数量
        if($is_split)
        {
            #发货单明细详情
            $deliItemDetailModel = &app::get('ome')->model('delivery_items_detail');
            $deliItemDetailList = $deliItemDetailModel->getList('order_id, order_obj_id, order_item_id, number',array('delivery_id'=>$value_list['ome_delivery_id']));
            
            $delivery_detail    = array();
            foreach ($deliItemDetailList as $key => $val)
            {
                $get_order_id   = $val['order_id'];
                $get_obj_id     = $val['order_obj_id'];
                $get_item_id    = $val['order_item_id'];
                
                $delivery_detail[$get_order_id][$get_obj_id][$get_item_id]  = $val;
            }
            
            #根据发货单_获取发货单上商品数量
            $sql    = "SELECT a.order_id, a.item_id, a.obj_id, a.nums, a.item_type, b.quantity 
                        FROM sdb_ome_order_items AS a LEFT JOIN sdb_ome_order_objects AS b ON a.obj_id=b.obj_id 
                        WHERE a.order_id in(".implode(',', $orderIds).") AND a.delete='false'";
            $pkg_items    = kernel::database()->select($sql);
            
            $order_product_obj    = $order_pkg_obj = array();
            foreach ($pkg_items as $key => $val)
            {
                $get_order_id   = $val['order_id'];
                $get_obj_id     = $val['obj_id'];
                $get_item_id    = $val['item_id'];
                
                $get_obj_quantity    = intval($val['quantity']);
                $get_item_nums       = intval($val['nums']);
                $get_dly_number      = intval($delivery_detail[$get_order_id][$get_obj_id][$get_item_id]['number']);#发货单_商品数量
                
                #重新计算_发货单上捆绑商品数量
                if($val['item_type'] == 'pkg')
                {
                    $order_pkg_obj[$get_order_id][$get_obj_id]['buy_nums']  = intval($get_dly_number / ($get_item_nums / $get_obj_quantity));
                }
                else 
                {
                    $order_product_obj[$get_order_id][$get_obj_id]['buy_nums']  = $get_dly_number;
                }
            }
        }

        $data['bn_spec_num_y'] = $data['bn_spec_num_n'] = '';

        $delivery_cfg = app::get('wms')->getConf('wms.delivery.status.cfg');
        #开启打印捆绑商品按钮
        if($delivery_cfg['set']['print_pkg_goods']){
            #根据订单，获取捆绑商品信息
            $pkg_info =$order_Objects->getList('order_id,obj_id,bn,name,quantity',array('obj_type'=>'pkg','order_id'=>$orderIds));
            foreach($pkg_info as $info){
                
                $get_order_id   = $info['order_id'];
                $get_obj_id     = $info['obj_id'];
                $dly_quantity   = $order_pkg_obj[$get_order_id][$get_obj_id]['buy_nums'];
                
                $info['quantity']    = ($is_split ? $dly_quantity : $info['quantity']);#[拆单]发货单商品数量
                if($info['quantity'] == 0)
                {
                    continue;#[拆单]货品不在本次发货单清单上
                }
                
                #打印捆绑商品信息
                $data['pkgbn_num_n'] .= $info['bn'].'  x  '.$info['quantity']."\n";
            }
        }

        #普通商品(货号+数据)
        $normalGoodsInfos = $order_Objects->getList('order_id,obj_id,bn,quantity', array('obj_type|noequal'=>'pkg', 'order_id' => $orderIds));
        foreach ($normalGoodsInfos as $normalGoodsInfo) {
            
            $get_order_id   = $normalGoodsInfo['order_id'];
            $get_obj_id     = $normalGoodsInfo['obj_id'];
            $dly_quantity   = $order_product_obj[$get_order_id][$get_obj_id]['buy_nums'];
            
            $normalGoodsInfo['quantity']    = ($is_split ? $dly_quantity : $normalGoodsInfo['quantity']);#[拆单]发货单商品数量
            if($normalGoodsInfo['quantity'] == 0)
            {
                continue;#[拆单]货品不在本次发货单清单上
            }
            
            if ($orderItemsInfo[$normalGoodsInfo['order_id']][$normalGoodsInfo['obj_id']]['delete'] == 'false') {
                $data['normal_good_n'] .= $normalGoodsInfo['bn'].'  x  '. $normalGoodsInfo['quantity']."\n";
                $data['normal_good'] .= $normalGoodsInfo['bn'].'  x  '. $normalGoodsInfo['quantity'] . ' , ';
            }
            if ($data['normal_good']) {
                $data['normal_good'] = trim($data['normal_good'], ' , ');
            }
        }

        $noFirst = false;
        if ($value_list['delivery_items']) {
            $totalNum = 0;
            $total_product_weight = 0;
            $i = 0;
            foreach ($value_list['delivery_items'] as $item){
                if ($item['addon']) {
                    $addon = sprintf(' %s', $item['addon']);
                } else {
                    $addon = '';
                }
                $totalNum = $totalNum + $item['number'];

                //商家编码+规格+数量+换行
                $bn = $item['bn'];
                if(substr($item['bn_dbvalue'], 0, 3)===':::') {
                    $bn = '';
                }

                $noFirst && $data['bn_spec_num_n'] .= ' , ';
                $noFirst && $data['bn_spec_num_y'] .= "\r\n";

                $noFirst && $data['bn_name_spec_amount'] .= ' , ';
                $noFirst && $data['bn_name_spec_amount_y'] .= "\r\n";
                $goods_bn = $this->get_goods_bn($bn);
                if(empty($bn) && empty($item['addon'])) {
                    $data['bn_spec_num_n'] .= '';
                    $data['bn_spec_num_y'] .= '';
                } else {
                    $data['bn_spec_num_n'].= $bn."  ". $item['addon'] . " x " . $item['number'];
                    $data['bn_spec_num_y'].= $bn."  ". $item['addon'] . " x " . $item['number'];
                }
                //
                $product = app::get('ome')->model('products')->dump(array('bn'=>$item['bn']),'weight');
                $total_product_weight+= ($product['weight']*$item['number']);
                //
                //货号+数量+货位
                $data['bn_amount_pos'].= $item['bn']." x ".$item['number'].' - '.$item['store_position']."\n";
                //货品名+数量+货位
                $data['name_amount_pos'].= $item['product_name']. $addon ." x ".$item['number'].' - '.$item['store_position']."\n";
                //货号+货品名+数量+货位
                $data['bn_name_amount_pos'].= $item['bn']." ：".$item['product_name']. $addon ." x ".$item['number'].' - '.$item['store_position']."\n";

                //货号+数量
                $data['bn_amount_n'].= $item['bn']." x ".$item['number']." , ";
                //货品名+数量
                $data['name_amount_n'].= $item['product_name']. $addon ." x ".$item['number']." , ";
                //货号+货品名+数量
                $data['bn_name_amount_n'].= $item['bn']." ：".$item['product_name']. $addon ." x ".$item['number']." , ";

                $data['bn_amount'].= "货号：".$item['bn']." 数量：".$item['number']."\n";
                //货品名+数量
                $data['name_amount'].= "货品名：".$item['product_name']. $addon ." 数量：".$item['number']."\n";
                //货号+货品名+数量
                $data['bn_name_amount'].= "货号：".$item['bn']." 货品名：".$item['product_name']. $addon ." 数量：".$item['number']."\n";
                //货品名+规格+数量
                $data['name_spec_amount']    .= $item['product_name']."  ". $item['addon'] . " x " . $item['number'];
                //货号+货品名+规格+数量
                $data['bn_name_spec_amount'] .=  $item['bn']."：".$item['product_name']."  ". $item['addon'] . " x " . $item['number'];
                $data['bn_name_spec_amount_y'] .=  $item['bn']."：".$item['product_name']."  ". $item['addon'] . " x " . $item['number'];

                $data['new_bn_name_amount'] .="【".$item['product_name']." x ".$item['number']." 】 ";
                $data['bn_spec_num'].= $item['bn']."  ". $item['addon'] . " x " . $item['number'];

                $data['goodsbn_spec_num_n'] .= $goods_bn."  ". $item['addon'] . " x " . $item['number'];
                $data['goodsbn_spec_num_y'] .= $goods_bn."  ". $item['addon'] . " x " . $item['number']."\n";

                $data['goods_bn'].= $goods_bn."\n";
                $data['bn_productname_spec_num_pos_n'].=$item['bn']."：".$item['product_name']."  ". $item['addon'] . " x " . $item['number'].'-'.$item['store_position']."\n";
                $data['bn_productname_spec_num_pos'].=$item['bn']."：".$item['product_name']."  ". $item['addon'] . " x " . $item['number'].'-'.$item['store_position'];
                $noFirst = true;
                $self_data[$i]['bn'] = $item['bn'].' ';
                $self_data[$i]['pos'] = $item['store_position'].' ';
                $self_data[$i]['name'] = $item['product_name'].' ';
                $self_data[$i]['spec'] = $item['addon'].' ';
                $self_data[$i]['amount'] = $item['number'].' ';
                $self_data[$i]['new_bn_name'] = $item['product_name'].' ';
                $self_data[$i]['goods_bn'] = $goods_bn.' ';
                $self_data[$i]['goods_bn2'] = $goods_bn.' ';//历史遗留问题，商家编码就是商品编号
                $self_data[$i]['n'] = "\n";
                $i++;
            }

            $data['bn_amount_n'] = preg_replace('/, $/is', '', $data['bn_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['name_amount_n'] = preg_replace('/, $/is', '', $data['name_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['bn_name_amount_n'] = preg_replace('/, $/is', '', $data['bn_name_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['total_product_weight_g'] = $total_product_weight.'g';//商品重量累加
            $data['total_product_weight_kg'] = ($total_product_weight/1000).'kg';//商品重量累加
        }

        //会员名
        if ($value_list['member_id']){
            $member = app::get('ome')->model('members')->dump($value_list['member_id']);
            $data['member_uname'] = $member['account']['uname'];
        }
        $data['ship_name']   = $value_list['consignee']['name'];
        $data['ship_addr']   = $value_list['consignee']['addr'];
        $data['ship_tel']    = $value_list['consignee']['telephone'];
        $data['ship_mobile'] = $value_list['consignee']['mobile'];
        $data['ship_zip']    = (string)$value_list['consignee']['zip'];
        $data['ship_area_0'] = trim($value_list['consignee']['province']);
        $data['ship_area_1'] = trim($value_list['consignee']['city']);
        $data['ship_area_2'] = trim($value_list['consignee']['district']);
        $data['ship_detailaddr'] = $value_list['consignee']['province'] . $value_list['consignee']['city'] . $value_list['consignee']['district']. $value_list['consignee']['addr'];
        $data['order_bn']    = (string)$value_list['order_bn'];
        $data['order_count'] = (string)$value_list['order_count'];
        $data['order_memo']  = (string)$value_list['order_memo'];
        $data['order_custom']  = (string)$value_list['order_custom'];
        $data['delivery_bn'] = (string)$value_list['delivery_bn'];
        $data['logi_no'] = (string)$value_list['logi_no'];
        $data['delyvery_memo'] = $value_list['memo'];

        //获取顺丰城市代码
        if (app::get('logisticsmanager')->is_installed()) {
            $sfcityCodeObj = app::get('logisticsmanager')->model('sfcity_code');
            $area_crc32 = sprintf('%u',crc32($data['ship_area_1']));
            $sfcity_code = $sfcityCodeObj->dump(array('city_crc32'=>$area_crc32,'province|head'=>$data['ship_area_0']),'city_code');
            $data['sfcity_code'] = $sfcity_code['city_code'];
        }
        if(isset($GLOBALS['user_timezone'])){
             $t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        }else{
             $t = time();
        }
        //$t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        $data['date_y'] = (string)date('Y',$t);
        $data['date_m'] = (string)date('m',$t);
        $data['date_d'] = (string)date('d',$t);
        $data['date_ymd'] = date('Y-m-d',$t);
        $data['date_h'] = date('H',$t);
        $data['date_i'] = date('i',$t);
        $data['date_s'] = date('s',$t);

        // 发货人信息
        if ($value_list['shopinfo']){
            $area = kernel::single('base_view_helper')->modifier_region($value_list['shopinfo']['area']);
            $area = explode('-',$area);
            $data['dly_area_0']     = $area[0];
            $data['dly_area_1']     = $area[1];
            $data['dly_area_2']     = $area[2];
            $data['dly_address']    = $value_list['shopinfo']['addr'];
            $data['dly_detailaddr'] = $area[0]. $area[1] . $area[2] . $value_list['shopinfo']['addr'];
            $data['dly_tel']        = (string)$value_list['shopinfo']['tel'];
            $data['dly_mobile']     = (string)$value_list['shopinfo']['mobile'];
            $data['dly_zip']        = (string)$value_list['shopinfo']['zip'];
            $data['dly_name']       = $value_list['shopinfo']['default_sender'];
            $data['shop_name']      = $value_list['shopinfo']['name'];
        }

        //根据自定义获取大头笔信息
        $this->getPrintTag($data);
        //获取云栈大头笔信息
        $corpType = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$value_list['logi_id'],'tmpl'=>'normal'),'type');
        $shopId = app::get('ome')->model('shop')->dump(array('shop_type'=>'taobao','node_id|noequal'=>''),'shop_id');       
        if($corpType['type']=='ZTO' && $shopId){
            //物流为中通时，通过云栈获取大头笔信息
            $this->router = kernel::single('apibusiness_router_request');
            $onlineData = $this->router->setShopId($shopId['shop_id'])->getCloudStackPrintTag($data,'ZTO',$shopId['shop_id']);
            if($onlineData->rsp == 'succ'){
                $waybill_distribute_info_response = json_decode($onlineData->data,ture);
                $data['cloud_stack_position'] = $waybill_distribute_info_response['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'][0]['short_address'];
            }
        }

        //面单扩展数据
        $data['mailno_position'] = '';//面单大头笔
        $data['mailno_position_'] = '';//面单大头笔编码
        $data['mailno_barcode'] = '';//面单条形码
        $data['mailno_qrcode'] = '';//面单二维码
        $data['package_wdjc'] = '';//集包地
        $data['package_wd'] = '';//集包地编码
        $data['batch_logi_no'] = '';
        $mainoInfo = $this->getMainnoInfo(array('delivery_id' => $value_list['delivery_id'], 'logi_id' => $value_list['logi_id']));
        if ($mainoInfo) {
            $data['mailno_position'] = $mainoInfo['position'];
            $data['mailno_position_no'] = $mainoInfo['position_no'];
            $data['mailno_barcode'] = $mainoInfo['mailno_barcode'];
            $data['mailno_qrcode'] = $mainoInfo['qrcode'];
            $data['package_wdjc'] = $mainoInfo['package_wdjc'];
            $data['package_wd'] = $mainoInfo['package_wd'];
        }
        $data['batch_logi_no'] = $value_list['batch_logi_no'];
        $data['package_number'] = '1/1';
        
        if ($data['logi_no']) {
            $pack_number = explode('-',$data['batch_logi_no']);
            
            if (count($pack_number)>=3) {
                $data['package_number']=$pack_number[1].'/'.$pack_number[2];
            }
        }
        $memo = '';
        if(!empty($value_list['memo']))
        {
            $memo = '   (' . $value_list['memo'] . ')';
        }

        $data['ship_addr_mark'] = $data['ship_addr'] . $memo;
        $data['ship_detailaddr_mark'] = $data['ship_detailaddr'] . $memo;

        $data['print_no'] = app::get('ome')->model('print_queue')->findFullIdent($value_list['delivery_id']);
        $_self_elments = app::get('wms')->getConf('wms.delivery.print.selfElments');
        #获取快递单对应的自定义打印项
        $self_elments = $_self_elments['element_'.$value_list['prt_tmpl_id']];
        if(isset($self_elments['element'])){
            $_key =  array_keys($self_elments['element']);
            $key = explode('+',$_key[0]);
            $str_self_elment = '';
            foreach($self_data as $_k=>$v){
                foreach($key as $val){
                    $str_self_elment .=  $v[$val].' ';
                }
            }
            #把原来键中的+号替换掉
            $new_key = str_replace('+', '_', $_key[0]);
            #自定义的打印项的值
            $data[$new_key] = $str_self_elment;
        }
        foreach($data as $k=>$v){
            $data[$k] = addslashes($v);
            unset($k,$v);
        }
        return $data;
    }

    //根据收货地区得到大头笔内容
    function getPrintTag(&$data) {
        $zhixiashi = array('北京','上海','天津','重庆');
        $areaGAT = array('香港','澳门','台湾');
        $area2Str = substr($data['ship_area_2'],-3);
        $printTagObj = app::get('wms')->model('print_tag');
        $rows = $printTagObj->getList('*');
        foreach($rows as $row){
            if($row['tag_id']>0){
                $key = 'print_tag_'.$row['tag_id'];
                $tagConfig= unserialize($row['config']);
                if ($data['ship_area_0'] && in_array($data['ship_area_0'],$zhixiashi)) {
                    if($tagConfig['zhixiashi'] == '1'){
                        $data[$key] = $data['ship_area_2'];
                    }else{
                        $data[$key] = $data['ship_area_1'].$data['ship_area_2'];
                    }
                } elseif($data['ship_area_0'] && in_array($data['ship_area_0'],$areaGAT)) {
                    if($tagConfig['areaGAT'] == '1'){
                        $data[$key] = $data['ship_area_2'];
                    }else{
                        $data[$key] = $data['ship_area_1'].$data['ship_area_2'];
                    }
                } else {
                    $data[$key] = '';
                    if($tagConfig['province'] == '1'){
                        $data[$key] .= $data['ship_area_0'];
                    }

                    if ($area2Str=='区') {
                        if($tagConfig['district'] == '1'){
                            $data[$key] .= $data['ship_area_1'];
                        }else{
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }
                    } elseif ($area2Str=='市') {
                        if($tagConfig['city'] == '1'){
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }else{
                            $data[$key] .= $data['ship_area_2'] ? $data['ship_area_2'] : $data['ship_area_1'];
                        }
                    } else {
                        if($tagConfig['county'] == '1'){
                            $data[$key] .= $data['ship_area_2'] ? $data['ship_area_2'] : $data['ship_area_1'];
                        }else{
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }
                    }
                }
            }
        }
    }

     function get_goods_bn($bn) {
        $db = kernel::database();
        $sql = 'SELECT g.bn FROM sdb_ome_products as p LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id WHERE p.bn=\''.$bn.'\'';
        $goods = $db->select($sql);
        return $goods[0]['bn'];
    }
    #转换为财务数字
    function financeNum($num){
        #只能处理到千亿
        if($data>999999999999){
            return '数据过大，请检查';
        }
        #检查是否包含小数点
        $_num   = explode('.',$num);
        $int_num = $_num[0];#整数部分
        $float_num = $_num[1];#小数部分
        
        $int_data = '';
        $float_data = '';
        
        $num = array(0=>'零',1=>'壹',2=>'贰',3=>'叁',4=>'肆',5=>'伍',6=>'陆',7=>'柒',8=>'捌',9=>'玖');#数字
        $unit = array(0=>'圆',1=>'拾',2=>'佰',3=>'仟',4=>'萬',5=>'拾',6=>'佰',7=>'仟',8=>'億');#数量单位
        $unit2 = array(0=>'圆',1=>'拾',2=>'佰',3=>'仟',4=>'萬',5=>'拾萬',6=>'佰萬',7=>'千萬',8=>'億',9=>'拾億',10=>'佰億');#数量单位
        	
        preg_match('/^[1-9]{1,}[0]{0,}$/',$int_num,$arr);
        #1、只有末尾有零的;2、中间不包含任何0的
        if(!empty($arr)){
            $str = strrev($int_num);#将字符串翻转
            $count = strlen($str);
            $int_data = '';
            for($i=0;$i<$count;$i++){
                if($str[$i] != 0 ){
                    $_str = $num[$str[$i]].$unit[$i];
                    $int_data = $_str.$int_data;
                }
            }
            $int_data .= '圆';
        }else{#中间有0的
            if($int_num == 0){
                $int_data = '零圆';
            }else{
            #需要检查数字的下一位是否包含0
            $str = strrev($int_num);#将字符串翻转
            $count = strlen($int_num);
            $int_data = '';
            for($i=0; $i<$count; $i++){
                if($str[$i] == 0){
                    $int_data[] = $num[$str[$i]];
                }else{
                    if($i>0){
                        if($str[$i-1] == 0){
                            $int_data[] = $num[$str[$i]].$unit2[$i];
                        }else{
                            $int_data[] = $num[$str[$i]].$unit[$i];
                        }
                    }else{
                        $int_data[] = $num[$str[$i]].$unit[$i];
                    }
                }
            }
            krsort($int_data);
            $_str_int_data =  implode('',$int_data);
            $int_data  = preg_replace('/[零]{2,}/','零',$_str_int_data);
            preg_match('/(.*)零$/',$int_data,$arr);#替换最后一个零
            if($arr){
                $int_data =  $arr[1];
            }
            $int_data .='圆';
            
    	}}
    	$int_data = str_replace('圆圆','圆',$int_data);
    	if($float_num>0){
    	    #小数点部分
    	    $count = strlen($float_num);
    	    $float_data = '';
    	    $unit = array(0=>'角',1=>'分');
    	    for($i=0;$i<$count;$i++){
    	        $_str = $num[$float_num[$i]].$unit[$i];
    	        $float_data = $float_data.$_str;
    	    } 
    	    return $int_data.$float_data;
    	}else{
    	    return $int_data;
    	}
    }           
    /**
     * 获取面单信息
     * @param Array 参数信息
     */
    function getMainnoInfo($params) {
        $logi_id = $params['logi_id'];
        $dlyCorpModel = app::get('ome')->model('dly_corp');
        $filter = array('corp_id' => $logi_id);
        $dlyData = $dlyCorpModel->getList('channel_id,tmpl_type', $filter);
        $mailnoExtend = array();
        if ($dlyData && $dlyData[0]['tmpl_type'] == 'electron') {
            $params['channel_id'] = $dlyData[0]['channel_id'];
            $mailnoExtend = kernel::single('logisticsmanager_service_waybill')->getWayBillExtend($params);
        }
        return $mailnoExtend;
    }    
}