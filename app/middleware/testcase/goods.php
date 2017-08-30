<?php
class goods extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 商品
    */
    public function testgoods(){

        #商品添加
        $sdf = array(
            array(
                'bn' => 'pbn1',
                'product_id' => '1',
                'name' => 'pbn1商品名称',
                'barcode' => 'pbn1-barcode',
                'unit' => '件',
                'weight' => '3G',
                'price' => '299',
                'package_spec' => '',
                'ename' => 'en',
                'brand' => '',
                'goods_cat' => '通用商品类型',
                'color' => '',
                'property' => '',
                'memo' => 'memo',
            ),
            array(
                'bn' => 'pbn2',
                'product_id' => '2',
                'name' => 'pbn2商品名称',
                'barcode' => 'pbn1-barcode',
                'unit' => '件',
                'weight' => '3G',
                'price' => '299',
                'package_spec' => '',
                'ename' => 'en',
                'brand' => '',
                'goods_cat' => '通用商品类型',
                'color' => '',
                'property' => '',
                'memo' => 'memo',
            )
        );
        $callback = array (
  0 => 'middleware_wms_matrixwms_request_goods',
  1 => 'goods_add_callback',
  2 => 
  array (
    'log_id' => '91e59a51f74426f17a8bb667a2b40e3b',
    'userCallback_class' => NULL,
    'userCallback_method' => NULL,
    'userCallback_params' => NULL,
    'callback_params' => NULL,
  ),
);
        $params = array (
  'items'=>'[{"product_name":"\u7537\u6b3e\u9ed1\u8272\u7537\u76ae\u978b-1005R","product_bn":"1005RW-060","barcode":"885641752675"}]',
  'uniqid' => 'f1e652ef6d16af79938cb68d3db0ac96',
  'node_id' => 'TBL',
  'to_node_id' => 'TBL',
            'method'=>'store.wms.item.add',
);
        $mode = 'false';
        $time_out=5;
         $callback_class = $callback[0];
        $callback_method = $callback[1];
        $method = 'store.wms.item.add';
        $callback_params = (isset($callback[2])&&$callback[2])?$callback[2]:array();
       $re =  kernel::single('rpc_caller')->conn('fsockopen')->set_timeout($time_out)->call('http://ytznewbalance.ftp.taoex.com/src/background.php',$method,$params);
       var_dump($re);
       $re = json_decode($re,true);
       error_log(var_export($re,1),3,__FILE__.'.log');
    }

}
