<?php
/**
* 售后 抽象类
*
* @category apibusiness
* @package apibusiness/response/aftersale
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_aftersale_abstract
{
    protected $_respservice = null;

    protected $_tgver = '';

    public $_apiLog = array();

    public $_aftersaleSdf = array();

    protected $_shop = array();

    const _APP_NAME = 'ome';

    public function __construct($aftersaleSdf)
    {
        $this->_aftersaleSdf = $aftersaleSdf;
        
    }

    public function setShop($shop)
    {
        $this->_shop = $shop;

        return $this;
    }

    /**
     * 添加售后申请
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        // 日志
        $this->_apiLog['title']  = '前端店铺售后申请接口[售后单号：'.$this->_aftersaleSdf['return_bn'].' ]';
        $this->_apiLog['info'][] = '接收参数：'.var_export($this->_aftersaleSdf,true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

        $order_bn  = $this->_aftersaleSdf['order_bn'];
        $return_bn = $this->_aftersaleSdf['return_bn'];
        $shop_id   = $this->_shop['shop_id'];
        $status    = $this->_aftersaleSdf['status'];

        if ($status == '') {
            $this->_apiLog['info']['msg'] = '返回值：售后状态不能为空';
            $this->exception(__METHOD__);
        }

        // 售后单
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $tgReturn = $returnModel->dump(array('shop_id'=>$shop_id,'return_bn'=>$return_bn));
        if ($tgReturn) {
            $this->_apiLog['info']['msg'] = '返回值：售后单已经存在';
            $this->exception(__METHOD__);
        }

        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn));
        if (empty($tgOrder)) {
            $this->_apiLog['info']['msg'] = '返回值：订单不存在';
            $this->exception(__METHOD__);
        }

        if ($tgOrder['ship_status'] == '0') {
            $this->_apiLog['info']['msg'] = '返回值：订单未发货不能申请售后';
            $this->exception(__METHOD__);
        }

        if ($tgOrder['ship_status'] == '4') {
            $this->_apiLog['info']['msg'] = '返回值：订单已经退货不能申请售后';
            $this->exception(__METHOD__);
        }

        if(is_string($this->_aftersaleSdf['return_product_items']));
            $return_product_items = json_decode($this->_aftersaleSdf['return_product_items'],true);

        if (!$return_product_items || !is_array($return_product_items)) {
            $this->_apiLog['info']['msg'] = '返回值：售后商品格式不正确';
            $this->exception(__METHOD__);
        }

        $productModel = app::get(self::_APP_NAME)->model('products');
        $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
        $oOrder_objects = app::get(self::_APP_NAME)->model('order_objects');
        $return_num = array(); $productList = array();
        $return_item = array();
        foreach ($return_product_items as $item) {
            $bn = $item['bn'];
            $order_id = $tgOrder['order_id'];
            $order_objects = $oOrder_objects->dump(array('bn'=>$bn,'order_id'=>$order_id),'obj_type,obj_id,quantity,sale_price');
            if ($order_objects['obj_type'] == 'pkg') {
                $obj_id = $order_objects['obj_id'];
                $order_items = $orderItemModel->getList('item_id,order_id,bn,sendnum,name,nums,sale_price',array('order_id'=>$tgOrder['order_id'],'obj_id'=>$obj_id,'delete'=>'false'));
            }else{
                $order_items = $orderItemModel->getList('item_id,order_id,bn,sendnum,name,nums,sale_price',array('order_id'=>$tgOrder['order_id'],'bn'=>$bn,'delete'=>'false'));
            }
            


            if (!$order_items) {
                $this->_apiLog['info']['msg'] = "返回值：订单明细不存在，货号[{$item['bn']}]";
                $this->exception(__METHOD__);
            }

            $return_num[$item['bn']] += $item['num'];
            
            $sendnum = 0;

            foreach ($order_items as $k=>$value) {
                $sendnum += $value['sendnum'];
                $product = $productModel->dump(array('bn'=>$value['bn']));
                if (!$product) {
                    $this->_apiLog['info']['msg'] = "返回值：货号[{$value['bn']}]不存在";
                    $this->exception(__METHOD__);
                }
                if (!$productList[$product['bn']]) {
                    $productList[$product['bn']] = $product;
                }
                //申请数量

                
                if ($order_objects['obj_type'] == 'pkg') {
                    $items = $orderItemModel->db->selectrow("SELECT sum(nums) as total_nums FROM sdb_ome_order_items WHERE order_id=".$tgOrder['order_id']." AND obj_id=".$obj_id);
                    $total_nums = $items['total_nums'];
                    $obj_sale_price = $order_objects['sale_price'];
                    $num = ($value['nums']/$order_objects['quantity'])*$item['num'];
                    $value['num'] = $num;
                    $value['price'] =  round(($obj_sale_price/$total_nums)*$num,2);
                }else{
                    $value['num'] =  $item['num'];
                    $value['price'] =  $value['sale_price'];
                }
                $return_item[] = $value;
            }
            
            if ($return_num[$item['bn']] > $sendnum) {
                $this->_apiLog['info']['msg'] = "返回值：货号[{$item['bn']}]超出订单发货数";
                $this->exception(__METHOD__);
            }
            unset($order_objects);
        }


        // 如果前端传了会员名
        if ($this->_aftersaleSdf['member_uname']) {
            $shopMemberModel = app::get(self::_APP_NAME)->model('shop_members');
            $member = $shopMemberModel->dump(array('shop_member_id'=>$this->_aftersaleSdf['member_uname'],'shop_id'=>$shop_id));
            $member_id = $member['member_id'];
        } else {
            $member_id = $tgOrder['member_id'];
        }

        $opinfo = kernel::single('ome_func')->get_system();
        $sdf = array(
            'return_bn'  => $return_bn,
            'attachment' => $this->_aftersaleSdf['attachment'] ? $this->_aftersaleSdf['attachment'] : null,
            'shop_id'    => $shop_id,
            'member_id'  => $member_id,
            'order_id'   => $tgOrder['order_id'],
            'title'      => $this->_aftersaleSdf['title'],
            'content'    => $this->_aftersaleSdf['content'], 
            'comment'    => $this->_aftersaleSdf['comment'],
            'memo'       => $this->_aftersaleSdf['memo'],
            'add_time'   => $this->_aftersaleSdf['add_time'] ? $this->_aftersaleSdf['add_time'] : time(),
            'status'     => $status,
            'op_id'      => $opinfo['op_id'],
        );

        // 售后物流
        if(is_string($this->_aftersaleSdf['logistics_info']))
            $logistics_info = json_decode($this->_aftersaleSdf['logistics_info'],true);

        if ($logistics_info) {
            $process_data = array(
                'shipcompany' => $logistics_info['logi_company'],
                'logino' => $logistics_info['logi_no'],
            );

            $sdf['process_data'] = serialize($process_data);
        }

        $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
        $deliOrder = $deliOrderModel->dump(array('order_id'=>$tgOrder['order_id']));
        if ($deliOrder) {
            $sdf['delivery_id'] = $deliOrder['delivery_id'];
        }
        $oDelivery = app::get(self::_APP_NAME)->model('delivery');
        $delivery = $oDelivery->dump(array('delivery_id'=>$sdf['delivery_id']),'branch_id');
        $rs = $returnModel->create_return_product($sdf);
        
        // 售后单明细
        $returnItemModel = app::get(self::_APP_NAME)->model('return_product_items');
        foreach ( $return_item as $item) {

            $rpi = array(
                'return_id'  => $sdf['return_id'],
                'product_id' => $productList[$item['bn']]['product_id'],
                'bn'         => $item['bn'],
                'name'       => $item['name'],
                'num'        => $item['num'],
                'price'   =>$item['price'],
                'branch_id'=>$delivery['branch_id'],
            );

            $returnItemModel->save($rpi);
        }

        $this->_apiLog['info'][] = 'O.K';
    }

    /**
     * 更新售后申请单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        // 日志
        $this->_apiLog['title']  = '前端店铺更新售后申请单状态[售后单号：'.$this->_aftersaleSdf['return_bn'].' ]';
        $this->_apiLog['info'][] = '接收参数：'.var_export($this->_aftersaleSdf,true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

        $status    = $this->_aftersaleSdf['status'];
        $return_bn = $this->_aftersaleSdf['return_bn'];
        $order_bn  = $this->_aftersaleSdf['order_bn'];
        $shop_id   = $this->_shop['shop_id'];

        if (!$status) {
            $this->_apiLog['info']['msg'] = 'no status';
            $this->exception(__METHOD__);
        }

        if (!$return_bn) {
            $this->_apiLog['info']['msg'] = 'no return bn';
            $this->exception(__METHOD__);
        }

        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }

        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn));
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order';
            $this->exception(__METHOD__);
        }

        // 售后单
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $tgReturn = $returnModel->dump(array('return_bn'=>$return_bn,'shop_id'=>$shop_id));
        if (!$tgReturn) {
            $this->_apiLog['info']['msg'] = 'no after-sales';
            $this->exception(__METHOD__);
        }

        $returnItemModel = app::get(self::_APP_NAME)->model('return_product_items');
        $tgReturnItems = $returnItemModel->getList('*',array('return_id'=>$tgReturn['return_id']));
        if (!$tgReturnItems) {
            $this->_apiLog['info']['msg'] = 'no after-sales detail';
            $this->exception(__METHOD__);
        }

        $data = array(
            'status'    => $status,
            'return_id' => $tgReturn['return_id'],
        );

        if (in_array($status, array('2','3'))) {
            foreach ($tgReturnItems as $key => $item) {
                $data['item_id'][$key]          = $item['item_id'];
                $data['effective'][$item['bn']] =  $item['num'];
                $data['bn'][$item['bn']]        = $item['num'];        
            }
            $returnModel->tosave($data);
        } elseif ($status == '4') {
            $totalmoney = 0;
            foreach ($tgReturnItems as $key => $item) {
                $data['branch_id'][$key]  = $item['branch_id'];
                $data['product_id'][$key] = $item['product_id'];
                $data['item_id'][$key]    = $item['item_id'];
                $data['effective'][$key]  = $item['num'];
                $data['name'][$key]       = $item['name'];
                $data['bn'][$key]         = $item['bn'];
                $data['deal'.$key]        = 1;
            }
            $data['totalmoney'] = $totalmoney;
            $data['tmoney']     = $totalmoney;
            $data['bmoney']     = 0;
            $data['memo']       = '';

            /*统计此次请求对应货号退货数量累加*/

            $can_refund = array();
            foreach($data['bn'] as $k=>$v){
               if(isset($can_refund[$v])){
                 $can_refund[$v]['num']++;
               }else{
                 $can_refund[$v]['num']=1;
                 $can_refund[$v]['effective'] = $data['effective'][$k];
               }
               if($can_refund[$v]['effective'] == 0){
                    $this->_apiLog['info']['msg'] = '货号为['.$v.']没有可申请量，请选择拒绝操作,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                    $this->exception(__METHOD__);
               }else if($can_refund[$v]['num'] > $can_refund[$v]['effective']){
                    $this->_apiLog['info']['msg'] = '货号为['.$v.']大于可申请量，请选择拒绝操作,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                    $this->exception(__METHOD__);
               }
            }

            $returnModel->saveinfo($data);
        } else {
            $returnModel->update(array('status'=>$status),array('return_id'=>$tgReturn['return_id']));
        }

        $this->_apiLog['info'][] = '店铺('.$this->_shop['name'].')更新售后状态：'.$status.',订单:'.$order_bn.',售后单号:'.$return_bn;

        $this->_apiLog['info'][] = 'O.K';
    }

    /**
     * 更新售后申请物流信息
     *
     * @return void
     * @author 
     **/
    public function logistics_update()
    {
        // 日志
        $this->_apiLog['title']  = '前端店铺更新售后申请物流信息[售后单号：'.$this->_aftersaleSdf['return_bn'].' ]';
        $this->_apiLog['info'][] = '接收参数：'.var_export($this->_aftersaleSdf,true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

        $order_bn  = $this->_aftersaleSdf['order_bn'];
        $return_bn = $this->_aftersaleSdf['return_bn'];
        $shop_id   = $this->_shop;

        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }

        if (!$return_bn) {
            $this->_apiLog['info']['msg'] = 'no return bn';
            $this->exception(__METHOD__);
        }

        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id));
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order';
            $this->exception(__METHOD__);
        }

        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $tgReturn = $returnModel->dump(array('return_bn'=>$return_bn,'order_id'=>$tgOrder['order_id']));
        if (!$tgReturn) {
            $this->_apiLog['info']['msg'] = 'no after-sales';
            $this->exception(__METHOD__);
        }

        if(is_string($this->_aftersaleSdf['logistics_info']))
            $logistics_info = json_decode($this->_aftersaleSdf['logistics_info'],true);

        if (!$logistics_info && !is_array($logistics_info)) {
            $this->_apiLog['info']['msg'] = 'no logistics-info';
            $this->exception(__METHOD__);
        }

        $process_data = unserialize($tgReturn['process_data']);
        $process_data['shipcompany'] = $logistics_info['logi_company'];
        $process_data['logino'] = $logistics_info['logi_no'];

        $returnModel->update(array('process_data'=>serialize($process_data)),array('return_id'=>$tgReturn['return_id']));
        
        $this->_apiLog['info'][] = 'O.K';
    }

    /**
     * 响应对象设置
     *
     * @return Object
     * @author 
     **/
    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;
        return $this;
    }

    /**
     * 淘管中对应版本
     *
     * @return Object
     * @author 
     **/
    public function setTgVer($tgver)
    {
        $this->_tgver = $tgver;
        return $this;
    }

    /**
     * 异常处理
     *
     * @return void
     * @author 
     **/
    protected function exception($fun,$retry='false')
    {
        $logModel = app::get('ome')->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $this->_apiLog['title'],
                             get_class($this), 
                             $fun, 
                             '', 
                             '', 
                             'response', 
                             'fail', 
                             implode('<hr/>',$this->_apiLog['info']),
                             '',
                             '',
                             $this->_aftersaleSdf['order_bn']);

        $data = array('tid'=>$this->_aftersaleSdf['order_bn'],'aftersale_id'=>$this->_aftersaleSdf['return_bn'],'retry'=>$retry);

        $this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        exit;
    }
}