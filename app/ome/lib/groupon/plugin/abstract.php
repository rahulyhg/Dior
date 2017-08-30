<?php
/**
 * 插件接口类
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

abstract class ome_groupon_plugin_abstract {

    private $_vaild_field = array ('order_bn' => '订单号', 'product_bn' => '货号', 'cost_item' => '商品总价', 'total_amount' => '订单总价' );
    private $_extend_vaild_field = array ('consignee' => '收货人信息','product_bn' => '货号' );

    public function getPluginName() {

        return $this->_name;
    }

    /**
     * 处理导入到原始数据
     *
     * @param array $data 原始数据
     * @return Array
     */
    public function process($data, $post) {
        $return = $this->convertToSdf ( $data, $post );
        if($return['rsp'] == 'fail'){
            return $return;
        }
        $orderSdfs = $return['data'];

        $num = 0;
        $groupon_id = 0;
        foreach ( $orderSdfs as $order ) {
            if(!$this->import ( $order)){
                return kernel::single ( 'ome_func' )->getErrorApiResponse ( '导入时发现订单号:' . $order['order_bn'] . ' 创建失败,系统已经存在此订单！在订单'.$order['order_bn'].'之前的订单导入成功，之后的订单需要重新导入。' );
            }

            if ($post ['is_pay'] == 'yes') {
                $payment_sdf = $this->getPaySdf($order,$post);
                $this->doPay ( $payment_sdf );
            }

            if($order['order_id']>0){
                $num++;
                if($num==1){
                    $groupon_id = $this->createOrderGroupon($post);
                    $this->createOrderGrouponItem($groupon_id, $order['order_id']);
                }elseif($num>1 && $groupon_id>0){
                    $this->createOrderGrouponItem($groupon_id, $order['order_id']);
                }
            }
            unset($order);
        }

        return array('rsp'=>'succ');
    }

    public function import(& $orderSdf) {
        $mdl = &app::get ( 'ome' )->model ( 'orders' );
        if ($mdl->create_order ( $orderSdf )) {
            return true;
        } else {
            return false;
        }
    }

    public function createOrderGroupon($post) {
        $mdl_order_groupon = &app::get('ome')->model('order_groupon');
        $sdf = array (
            'name' => $post ['groupon_name'],
            'shop_id' => $post ['shop_id'],
            'create_time' => time (),
            'opt_id' => kernel::single ( 'desktop_user' )->get_id (),
            'opt_name' => kernel::single ( 'desktop_user' )->get_name ()
        );
        $groupon_id = $mdl_order_groupon->insert ( $sdf );

        return $groupon_id;
    }

    public function createOrderGrouponItem($groupon_id, $order_id) {
        $mdl_order_groupon_items = &app::get ('ome')->model('order_groupon_items');
        $order_groupon_items_sdf = array (
            'order_groupon_id' => $groupon_id,
            'order_id' => $order_id
        );
        $item_id = $mdl_order_groupon_items->insert($order_groupon_items_sdf);

        return $item_id;
    }

    public function convertToSdf($data, $post) {
        $orderSdfs = array ();
        $msgList = array ();

        foreach ( $data as $k => $row ) {
            $row_sdf = $this->convertToRowSdf ( $row, $post );
            $msg = '';
            if ($this->vaildRowSdf ( $row_sdf, $msg )) {
                $data [$k] = $row_sdf;
            } else {
                $msgList[] = '订单号:'.$row_sdf['order_bn'].','.$msg;
                //return kernel::single ( 'ome_func' )->getErrorApiResponse ( '第' . ($k + 1) . '行数据报错:' . $msg );
            }
        }

        if (count ( $msgList ) > 0) {
            $errorMsg[] = "预检发现 ".count($msgList)." 个错误，本次导入任务失败";
            foreach($msgList as $key=>$val){
                $errorMsg[] = ($key+1)."、".$val;
            }
            return kernel::single ( 'ome_func' )->getErrorApiResponse ($errorMsg);
        }

        foreach ( $data as $k => $row ) {
            $orderSdf = $row;
            $orderObjectItem = 0;

            if( preg_match_all('/:\d{1,}\/$/is', $row['product_bn'],$mathes)){
                $products = explode('/',$row['product_bn']);
                foreach($products as $key=>$val){
                    $product = explode(':',$val);
                    if($product[1]>0){
                        $productBns[$key]['bn'] = $product[0];
                        $productBns[$key]['number'] = $product[1];
                    }
                }
            }else{
                $productBns[0]['bn'] = $row['product_bn'];
                $productBns[0]['number'] = $row['product_nums'];
            }

            foreach($productBns as $item){
                $product_info = app::get ( 'ome' )->model ( 'products' )->dump ( array ('bn' => $item['bn'] ) );
                if (! $product_info) {
                    foreach ( kernel::servicelist ( 'ome.product' ) as $name => $object ) {
                        if (method_exists ( $object, 'getProductInfoByBn' )) {
                            $product_data = $object->getProductInfoByBn ( $item['bn'] );
                            if ($product_data) {
                                $orderSdf ['order_objects'] [$orderObjectItem] ['bn'] = $item['bn'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['name'] = $product_data['name'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['quantity'] = $item['number'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['price'] = $row ['product_price'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['amount'] = $row ['product_price'] * $item['number'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['sale_price'] = $row ['product_price'] * $item['number'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['obj_type'] = $product_data ['product_type'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['obj_alias'] = $product_data ['product_desc'];
                                $orderSdf ['order_objects'] [$orderObjectItem] ['goods_id'] = $product_data ['goods_id'];
                                if ($product_data ['items']) {
                                    foreach ( $product_data ['items'] as $inc => $iv ) {
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['bn'] = $iv ['bn'];
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['name'] = $iv ['name'];
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['quantity'] = $iv ['nums'] * $item['number'];
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['price'] = $iv ['price'] ? $iv ['price'] : 0;
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['amount'] = $iv ['nums'] * ($iv ['price'] ? $iv ['price'] : 0);
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['sale_price'] = $iv ['nums'] * ($iv ['price'] ? $iv ['price'] : 0);
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['item_type'] = $product_data ['product_type'];
                                        $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$inc] ['product_id'] = $iv ['product_id'];
                                    }
                                } else {
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['bn'] = $item['bn'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['name'] = $product_data['name'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['quantity'] = $item['number'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['price'] = $row ['product_price'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['amount'] = $row ['product_price'] * $item['number'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['sale_price'] = $row ['product_price'] * $item['number'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['item_type'] = $product_data ['product_type'];
                                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['product_id'] = $product_data ['product_id'];
                                }
                                break;
                            }
                        }
                    }
                } else {
                    $orderSdf ['order_objects'] [$orderObjectItem] ['bn'] = $item['bn'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['name'] = $product_info['name'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['quantity'] = $item['number']; 
                    $orderSdf ['order_objects'] [$orderObjectItem] ['price'] = $row ['product_price'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['amount'] = $row ['product_price'] * $item['number'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['sale_price'] = $row ['product_price'] * $item['number'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['obj_type'] = 'goods'; //写死一个object一个item，并且类型是goods
                    $orderSdf ['order_objects'] [$orderObjectItem] ['obj_alias'] = '商品'; //写死一个object一个item，并且类型是商品
                    $orderSdf ['order_objects'] [$orderObjectItem] ['goods_id'] = $product_info ['goods_id'];

                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['bn'] = $item['bn'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['name'] = $product_info['name'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['quantity'] = $item['number'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['price'] = $row ['product_price'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['amount'] = $row ['product_price'] * $item['number'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['sale_price'] = $row ['product_price'] * $item['number'];
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['obj_type'] = 'product'; //写死product
                    $orderSdf ['order_objects'] [$orderObjectItem] ['order_items'] [$orderObjectItem] ['product_id'] = $product_info ['product_id'];
                }
                $orderObjectItem++;
            }

            //处理店铺信息
            $shop = app::get ( 'ome' )->model ( 'shop' )->dump ( array ('shop_id' => $post ['shop_id'] ) );
            $orderSdf ['shop_id'] = $shop ['shop_id'];
            $orderSdf ['shop_type'] = $shop ['shop_type'];
            $orderSdf ['createtime'] = time ();
            $orderSdf ['consignee'] ['area'] = $orderSdf ['consignee'] ['area'] ['province'] . "/" . $orderSdf ['consignee'] ['area'] ['city'] . "/" . $orderSdf ['consignee'] ['area'] ['county'];
            $orderSdf ['shipping'] ['is_cod'] = $orderSdf ['shipping'] ['is_cod'] ? strtolower ( $orderSdf ['shipping'] ['is_cod'] ) : 'false';
            $orderSdf ['is_tax'] = $orderSdf ['is_tax'] ? strtolower ( $orderSdf ['is_tax'] ) : 'false';
            $orderSdf ['cost_tax'] = $orderSdf ['cost_tax'] ? $orderSdf ['cost_tax'] : '0';
            $orderSdf ['discount'] = $orderSdf ['discount'] ? $orderSdf ['discount'] : '0';
            $orderSdf ['score_g'] = $orderSdf ['score_g'] ? $orderSdf ['score_g'] : '0';
            $orderSdf ['cost_item'] = $orderSdf ['cost_item'] ? $orderSdf ['cost_item'] : '0';
            $orderSdf ['total_amount'] = $orderSdf ['total_amount'] ? $orderSdf ['total_amount'] : '0';
            $orderSdf ['pmt_order'] = $orderSdf ['pmt_order'] ? $orderSdf ['pmt_order'] : '0';
            $orderSdf ['pmt_goods'] = $orderSdf ['pmt_goods'] ? $orderSdf ['pmt_goods'] : '0';
            $orderSdf ['custom_mark'] = kernel::single ( 'ome_func' )->append_memo ( $orderSdf ['custom_mark'] );
            $orderSdf ['mark_text'] = kernel::single ( 'ome_func' )->append_memo ( $orderSdf ['mark_text'] );
            $orderSdf ['order_source'] = 'groupon';
            $orderSdf ['source'] = 'local';
            $orderSdf['createway'] = 'import';

            $orderSdfs [] = $orderSdf;
            unset($productBns,$orderSdf);
        }

        return kernel::single ( 'ome_func' )->getApiResponse ( $orderSdfs );
    }

    public function doPay($payment_sdf) {
        $orderObj = &app::get ( 'ome' )->model ( 'orders' );
        $paymentObj = &app::get ( 'ome' )->model ( 'payments' );
        $paymentObj->create_payments ( $payment_sdf );

        // 更新订单的支付方式
        $orderObj->update ( array ('payment' => $payment_sdf ['paymethod'] ), array ('order_id' => $payment_sdf ['order_id'] ) );
    }

    public function getPaySdf($order_sdf, $post) {
        $payment_money = $order_sdf ['total_amount'];
        $cur_money = $payment_money ? $payment_money : '0';
        $paymentObj = &app::get ( 'ome' )->model ( 'payments' );
        $payment_bn = $paymentObj->gen_id ();
        $paymentCfgObj = &app::get ( 'ome' )->model ( 'payment_cfg' );
        $cfg = $paymentCfgObj->dump ( $_POST ['payment'] );
        $sdf = array ('payment_bn' => $payment_bn, 'shop_id' => $order_sdf ['shop_id'], 'order_id' => $order_sdf ['order_id'], 'account' => $post ['account'], 'bank' => $post ['bank'], 'pay_account' => $post ['pay_account'], 'currency' => $order_sdf ['currency'] ? $order_sdf ['currency'] : 'CNY', 'money' => $payment_money ? $payment_money : '0', 'paycost' => $order_sdf ['paycost'] ? $order_sdf ['paycost'] : 0, 'cur_money' => $cur_money, 'pay_type' => $post ['pay_type'] ? $post ['pay_type'] : 'online', 'payment' => $post ['payment'], 'pay_bn' => '', 'paymethod' => $cfg ['custom_name'], 't_begin' => time (), 'download_time' => time (), 't_end' => time (), 'status' => 'succ', 'memo' => $post ['memo'], 'is_orderupdate' => 'true', 'trade_no' => '' );

        return $sdf;
    }

    public function vaildRowSdf(& $row_sdf, & $msg) {
        foreach ( $this->_vaild_field as $field => $des ) {
            if (! isset ( $row_sdf [$field] ) || empty ( $row_sdf [$field] )) {
                $msg = $des . '为空!';
                return false;
            }
        }

        foreach ( $this->_extend_vaild_field as $field => $des ) {
            $method = 'vaild' . ucfirst ( $field );
            if (method_exists ( $this, $method )) {
                if (! $this->{$method} ( $row_sdf, $msg )) {
                    $msg = $des . ' ' . $msg;
                    return false;
                }
            }
        }

        return true;
    }

    public function vaildConsignee(& $row_sdf, & $msg) {
        $list = array ();
        $consignee_area_list = array ('province', 'city', 'county' );
        $consignee_list = array ('name', 'addr' );

        /*foreach($consignee_area_list as $k=>$col){
            if(!isset($row_sdf['consignee']['area'][$col]) || empty($row_sdf['consignee']['area'][$col])){
                $msg = $col . '为空!';
                return false;
            }
        }*/

        foreach ( $consignee_list as $k => $col ) {
            if (! isset ( $row_sdf ['consignee'] [$col] ) || empty ( $row_sdf ['consignee'] [$col] )) {
                $msg = $col . '为空!';
                return false;
            }
        }

        $regionLib = kernel::single('eccommon_regions');
        if (is_array ( $row_sdf ['area'] )) {
            foreach ( $row_sdf ['area'] as $k => $v ) {
                $row = $regionLib->getOneByName($v);
                if ($row) {
                    $list [] = $row;
                } else {
                    $list [] = '';
                }
            }
        }

        foreach ( $consignee_area_list as $k => $col ) {
            $row_sdf ['area'] [$col] = $list [$k];
        }

        return true;
    }

    public function vaildProduct_bn(& $row_sdf, & $msg) {
        if( preg_match_all('/:\d{1,}\/$/is', $row_sdf['product_bn'],$mathes)){
            $products = explode('/',$row_sdf['product_bn']);
            foreach($products as $key=>$val){
                $product = explode(':',$val);
                if($product[1]>0){
                    $productBns[$key]['bn'] = $product[0];
                    $productBns[$key]['number'] = $product[1];
                }
            }
        }else{
            $productBns[0]['bn'] = $row_sdf['product_bn'];
            $productBns[0]['number'] = $row_sdf['product_nums'];
        }

        foreach($productBns as $item){
            //验证商品
            $row = kernel::database()->selectrow('select name from sdb_ome_products where bn = "'. $item['bn'] .'"');
            if(!$row){
                //验证捆绑商品
                $pkgrow = kernel::database()->selectrow('select name from sdb_omepkg_pkg_goods where pkg_bn = "'. $item['bn'] .'"');
                if(!$pkgrow){
                    $msg = $item['bn'].' 没有此商品';
                    return false;
                }
            }
        }
        return true;
    }
}