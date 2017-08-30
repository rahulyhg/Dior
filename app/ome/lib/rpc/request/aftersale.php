<?php
/**
 * 售后服务同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_aftersale extends ome_rpc_request {
    
    //售后服务状态
    var $status = array (
        '1' => '1',#申请中',
        '2' => '2',#审核中',
        '3' => '3',#接受申请',
        '4' => '4',#完成',
        '5' => '5',#拒绝',
        '6' => '6',#已收货',
        '7' => '7',#已质检',
        '8' => '8',#补差价',
        '9' => '9',#已拒绝退款',
    );
    //售后状态值
    var $status_name = array (
        '1' => '申请中',
        '2' => '审核中',
        '3' => '接受申请',
        '4' => '完成',
        '5' => '拒绝',
        '6' => '已收货',
        '7' => '已质检',
        '8' => '补差价',
        '9' => '已拒绝退款',
    );
    
    /**
     * 添加售后申请
     * @access public
     * @param int $return_id 售后服务记录ID
     * @return boolean
     */
    public function add($return_id){
        
        if(!empty($return_id)){
            
            $returnObj = &app::get('ome')->model('return_product');
            $return_itemsObj = &app::get('ome')->model('return_product_items');
            $productObj = &app::get('ome')->model('products');
            $orderObj = &app::get('ome')->model('orders');
            $memberObj = &app::get('ome')->model('members');
            $return_product_items = $return_itemsObj->getList('name as sku_name,bn as sku_bn,num as number',array("return_id"=>$return_id),0,-1);
            $return_product_detail = $returnObj->dump(array('return_id'=>$return_id), '*');
            $order_detail = $orderObj->dump($return_product_detail['order_id'],'shop_id,member_id');
            $member_detail = $memberObj->dump(array('member_id'=>$order_detail['member_id']));
            $member_id = $member_detail['account']['uname'];
      
            //售后货品
            $return_productitems = array();
            if ($return_product_items)
            foreach ($return_product_items as $k=>$v){
                $return_productitems[] = $v;
            }
            
            //退货附件
            $attachment = $return_product_detail['attachment'];
            if (is_numeric($attachment)){
                $attachment = kernel::single('base_storager')->getUrl($attachment);
            }
            
            $order = $orderObj->dump($return_product_detail['order_id'], 'order_bn');
            $params['tid'] = $order['order_bn'];
            $params['aftersale_id'] = $return_product_detail['return_bn'];
            $params['title'] = $return_product_detail['title'] ? $return_product_detail['title'] : '';
            $params['content'] = $return_product_detail['content'] ? $return_product_detail['content'] : '';
            $params['messager'] = $return_product_detail['comment'] ? $return_product_detail['comment'] : '';
            if (!$return_product_detail['add_time']){
                $return_product_detail['add_time'] = time();
            }
            $params['created'] = date("Y-m-d H:i:s",$return_product_detail['add_time']);
            $params['memo'] = $return_product_detail['memo'] ? $return_product_detail['memo'] : '';
            $params['status'] = $this->status[$return_product_detail['status']];
            $params['buyer_id'] = $order_detail['member_id'];
            $params['buyer_name'] = $member_id;
            $params['aftersale_items']= json_encode($return_productitems);
            $params['attachment'] = $attachment;
            if (!$return_product_detail['last_modified']){
                $return_product_detail['last_modified'] = time();
            }
            $params['modify'] = date("Y-m-d H:i:s",$return_product_detail['last_modified']);

            $callback = array(
                'class' => 'ome_rpc_request_aftersale',
                'method' => 'aftersale_add_callback',
            );
            
            $shop_id = $order_detail['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')售后申请(订单号:'.$order['order_bn'].',申请单号:'.$return_product_detail['return_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.aftersale.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }

    }
    
    function aftersale_add_callback($result){
        return $this->callback($result);
    }
    
    /**
     * 更新售后服务状态
     * @access public
     * @param int $return_id 售后服务记录ID
     * @return boolean
     */
    public function status_update($return_id){

        if(!empty($return_id)){
            $orderObj = &app::get('ome')->model('orders');
            $returnObj = &app::get('ome')->model('return_product');
            
            $return_product_detail = $returnObj->dump(array('return_id'=>$return_id), 'return_bn,status,order_id');
            if ($return_product_detail['status'] == '4'){
                $product_detail = $returnObj->product_detail($return_id);
                if ($product_detail['check_data'])
                foreach ($product_detail['check_data'] as $item){
                    $tmp = array(
                        'bn' => $item['bn'],//货品货号
                        'name' => $item['name'],//货品名称
                        'memo' => $item['memo'],//备注
                        'need_money' => $item['need_money'],//应退金额
                        'other_money' => $item['other'],//折旧（其他金额）
                        'status' => $item['status'],//1：退货、2：换货、3：拒绝
                    );
                    $addon[] = $tmp;
                }
            }
            $order = $orderObj->dump($return_product_detail['order_id'], 'order_bn,shop_id');
            $params['tid'] = $order['order_bn'];
            $params['aftersale_id'] = $return_product_detail['return_bn'];
            $params['status'] = $this->status[$return_product_detail['status']];
            $status_name = $this->status_name[$return_product_detail['status']];
            $params['modify'] = date('Y-m-d H:i:s', time());
            $params['addon'] = json_encode($addon);

            $callback = array(
                'class' => 'ome_rpc_request_aftersale',
                'method' => 'aftersale_status_update_callback',
            );
         
            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新售后申请状态['.$status_name.'](订单号:'.$order['order_bn'].',申请单号:'.$return_product_detail['return_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.aftersale.status.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }
    
    function aftersale_status_update_callback($result){
        return $this->callback($result);
    }
    
    
}