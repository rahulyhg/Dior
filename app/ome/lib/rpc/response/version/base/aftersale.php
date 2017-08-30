<?php
/**
 * 前端店铺售后业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_base_aftersale extends ome_rpc_response
{

    /**
     * 添加售后申请
     * @access public
     * @param array $return_sdf 售后申请单数据
     * @param object $responseObj 框架API接口实例化对象
     * @return array('aftersale_id'=>'售后申请主键ID')
     */
    function add($return_sdf){

        $oApi_log = &app::get('ome')->model('api_log');
        $logTitle = '前端店铺售后申请接口[售后单号：'.$return_sdf['return_bn'].' ]';

        //返回值
        $rs_data = array('tid'=>$return_sdf['order_bn'],'aftersale_id'=>$return_sdf['return_bn'],'retry'=>'false');
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        /*Log info*/
        $logInfo = '前端店铺售后申请接口：<BR>';
        $logInfo .= '接收参数 $return_sdf 信息：' . var_export($return_sdf, true) . '<BR>';

        /*Log info*/

        $order_bn = $return_sdf['order_bn'];
        $return_bn = $return_sdf['return_bn'];
        $shop_id = $return_sdf['shop_id'];
        $shop_name = $return_sdf['shop_name'];
        $logInfo .= '店铺ID：' . $shop_id . '<BR>';


        $status = $return_sdf['status'];
        $addon['bn'] = $return_bn;
        $returnObj = &app::get('ome')->model('return_product');
        $return_itemsObj = &app::get('ome')->model('return_product_items');
        $productObj = &app::get('ome')->model('products');
        $orderObj = &app::get('ome')->model('orders');
        $dlyOrderObj = &app::get('ome')->model('delivery_order');
        $orderitemsObj = &app::get('ome')->model('order_items');
        $shop_memberObj = &app::get('ome')->model('shop_members');
        $return_product_detail = $returnObj->dump(array('return_bn'=>$return_bn,'shop_id'=>$shop_id),'return_bn');
        $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'order_id,ship_status,member_id');
        $refund_info_money = json_decode($return_sdf['refund_info'],true);

        $member_uname = $return_sdf['member_uname'];
        if ($member_uname){
            $shopmember_info = $shop_memberObj->dump(array('shop_member_id'=>$member_uname, 'shop_id'=>$shop_id));
            $member_id = $shopmember_info['member_id'];
        }else{
            $member_id = $order_detail['member_id'];
        }

        //判断订单是否存在
        if(!$order_detail['order_id']){
            $msg = 'order_bn '.$order_bn.' not exists!';
            $logInfo .= $msg;
            //日志记录
            /*
            $api_filter = array('marking_value'=>$order_bn,'marking_type'=>'aftersale');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $log_title = '店铺('.$shop_name.')售后申请,订单号:'.$order_bn.'不存在,售后申请单号:'.$return_bn;
                $logTitle = $log_title;
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
            }
            */
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;
            //$rs['logTitle'] = $logTitle;
            return $rs;
        }
        //判断售后申请单是否已经存在
        if($return_product_detail['return_bn']){
            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
        //判断状态
        if (!$status) {
            $msg = 'status: '.$status.' is not correct!';
            $logInfo .= $msg;
            //日志记录
            /*
            $api_filter = array('marking_value'=>$status,'marking_type'=>'aftersale');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $log_title = '店铺('.$shop_name.')售后申请,状态'.$status.'不正确,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                $logTitle = $log_title;
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
            }
            */
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;
            //$rs['logTitle'] = $logTitle;
            return $rs;
        }
        //判断订单状态是否满足售后条件 ：只要不是未发货/已退货即可申请
        if (in_array($order_detail['ship_status'], array('0','4'))){
            $msg = 'Orders not shipped or has been returned!';
            $logInfo .= $msg;
            //日志记录
            /*
            $api_filter = array('marking_value'=>$status,'marking_type'=>'aftersale');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $log_title = '店铺('.$shop_name.')售后申请[订单未发货或已退货],订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                $logTitle = $log_title;
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
            }
            */
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;
            //$rs['logTitle'] = $logTitle;
            return $rs;
        }
        //操作工号op_id
        $user_info = kernel::database()->selectrow("SELECT user_id FROM sdb_desktop_users WHERE super='1' ORDER BY user_id asc ");
        $op_id = $user_info['user_id'];
        //售后货品
        $return_product_items = json_decode($return_sdf['return_product_items'],true);

        if (!$return_product_items){
            $msg = 'Sale of goods does not exist!';
            $logInfo .= $msg;
            //日志记录
            /*
            $api_filter = array('marking_value'=>'items_is_empty','marking_type'=>'aftersale');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $log_title = '店铺('.$shop_name.')售后申请货品不能为空,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                $logTitle = $log_title;
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
            }
            */
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;
            //$rs['logTitle'] = $logTitle;
            return $rs;
        }
        foreach ($return_product_items as $k=>$items)
        {
            $product_info = $productObj->dump(array('bn'=>$items['bn']),"product_id");
            if(!$product_info){

                $msg = 'bn: '.$items['bn'].' not exists!';
                $logInfo .= $msg;
                //日志记录
                /*
                $api_filter = array('marking_value'=>$items['bn'],'marking_type'=>'aftersale');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])){
                    $log_title = '店铺('.$shop_name.')售后申请货品'.$items['bn'].'不存在,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                    $logTitle = $log_title;
                    $addon = $api_filter;
                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
                }
                */
                $rs['msg'] = $msg;
                $rs['logInfo'] = $logInfo;
                //$rs['logTitle'] = $logTitle;
                return $rs;
            }
            $tmp_bn[$items['bn']] += $items['num'];
            $order_items = $orderitemsObj->getList('item_id,order_id,bn,sendnum',array('order_id'=>$order_detail['order_id'],'bn'=>$items['bn'],'delete'=>'false'),0,-1);
            //判断货品是否属于订单
            if (!$order_items){
                $msg = 'bn: '.$items['bn'].' not exists by order!';
                $logInfo .= $msg;
                //日志记录
                /*
                $api_filter = array('marking_value'=>$items['bn'],'marking_type'=>'aftersale');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])){
                    $log_title = '店铺('.$shop_name.')售后申请货品'.$items['bn'].'不存在订单中';
                    $logTitle = $log_title;
                    $addon = $api_filter;
                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
                }
                */
                $rs['msg'] = $msg;
                $rs['logInfo'] = $logInfo;
                //$rs['logTitle'] = $logTitle;
                return $rs;
            }
            foreach ($order_items as $val){
                $p_bn[$val['bn']] += $val['sendnum'];
            }
            //判断此货品数量是否超过订单中该货品的总数量
            if ($tmp_bn[$items['bn']] > $p_bn[$items['bn']]){
                $msg = 'bn: '.$items['bn'].' exceeded the total number!';
                $logInfo .= $msg;
                //日志记录
                /*
                $api_filter = array('marking_value'=>$items['bn'],'marking_type'=>'aftersale');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])){
                    $log_title = '店铺('.$shop_name.')售后申请货品'.$items['bn'].'超出订单中此货品的总数';
                    $logTitle = $log_title;
                    $addon = $api_filter;
                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_aftersale','add','','','response','fail',$msg,$addon,'api.store.trade.aftersale',$return_bn);
                }
                */
                $rs['msg'] = $msg;
                $rs['logInfo'] = $logInfo;
                //$rs['logTitle'] = $logTitle;
                return $rs;
            }
        }
        $order_id = $order_detail['order_id'];
        //售后物流信息
        $logistics_info = json_decode($return_sdf['logistics_info'], true);
        if (!empty($logistics_info)){
            $process_data = array(
                'shipcompany' => $logistics_info['logi_company'],
                'logino' => $logistics_info['logi_no'],
            );
        }

        $sdf = array(
            'return_bn' => $return_bn,
            'attachment' => empty($return_sdf['attachment'])?null:$return_sdf['attachment'],
            'shop_id' => $shop_id,
            'member_id' => $member_id,
            'order_id' => $order_id,
            'title' => $return_sdf['title'],
            'content' => $return_sdf['content'],
            'comment' => $return_sdf['comment'],
            'memo' => $return_sdf['memo'],
            'add_time' => $return_sdf['add_time']?$return_sdf['add_time']:0,
            'status' => $status,
            'op_id' => $op_id,
            'attachment' => $return_sdf['attachment'],
            'process_data' => serialize($process_data),
        );
        //给前端的售后申请预设一个发货地址
        $dlyOrder = $dlyOrderObj->getList('delivery_id', array('order_id'=>$order_id), 0,-1);

        if(!empty($dlyOrder[0]['delivery_id'])){
             $sdf['delivery_id'] = $dlyOrder[0]['delivery_id'];
        }

        $returnObj->create_return_product($sdf);

        foreach ($return_product_items as $k=>$items)
        {
            $product_items  = array();
            $product_info = $productObj->dump(array('bn'=>$items['bn']),"product_id");
            $product_items['return_id'] = $sdf['return_id'];
            $product_items['product_id'] = $product_info['product_id'];
            $product_items['bn'] = $items['bn'];
            $product_items['name'] = $items['name'];
            $product_items['num'] = $items['num'];
            $return_itemsObj->save($product_items);
        }
        $rs['rsp'] = 'success';
        $rs['logInfo'] = $logInfo;
        return $rs;
    }

    /**
     * 更新售后申请单状态
     * @access public
     * @param array $status_sdf 售后状态数据
     * @param object $responseObj 框架API接口实例化对象
     */
    function status_update($status_sdf){

        /*Log info*/
        $log = &app::get('ome')->model('api_log');
        $logTitle = '前端店铺更新售后申请单状态[售后单号：]' . $status_sdf['return_bn'];
        $logInfo = '前端店铺更新售后申请单状态接口：<BR>';
        $logInfo .= '接收参数 $status_sdf 信息：' . var_export($status_sdf, true) . '<BR>';

        /*Log info*/

        $status = $status_sdf['status'];
        $return_bn = $status_sdf['return_bn'];
        $order_bn = $status_sdf['order_bn'];
        //返回值
        $rs_data = array('tid'=>$order_bn,'aftersale_id'=>$return_bn,'retry'=>'false');
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        //状态值判断
        if ($status==''){
            $rs['msg'] = 'Status field value is not correct';
            $logInfo .= $msg;
            $rs['logInfo'] = $logInfo;
            return $rs;
        }

        if ($return_bn!='' and $order_bn!=''){
            $shop_addon['bn'] = $return_bn;
            $shop_id = $status_sdf['shop_id'];
            $orderObj = &app::get('ome')->model('orders');
            $returnObj = &app::get('ome')->model('return_product');
            $return_itemsObj = &app::get('ome')->model('return_product_items');
            $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'order_id');
            $return_product_detail = $returnObj->dump(array('return_bn'=>$return_bn,'shop_id'=>$shop_id));
            $shop_name = $status_sdf['shop_name'];

            //判断订单是否存在
            if(!$order_detail['order_id']){
                $msg = '店铺('.$shop_name.')更新售后状态,订单号'.$order_bn.'不存在';
                $logInfo .= $msg;
                $rs['msg'] = $msg;
                $rs['logInfo'] = $logInfo;
                $rs['rsp'] = 'success';
                return $rs;
            }

            //判断售后单是否存在
            if(!$return_product_detail['return_id']){
                $msg = '店铺('.$shop_name.')更新售后状态,订单:'.$order_bn.',售后单号:'.$return_bn.'不存在';
                $logInfo .= $msg;
                $rs['rsp'] = 'success';
                $rs['msg'] = $msg;
                $rs['logInfo'] = $logInfo;
                return $rs;
            }

            $order_id = $order_detail['order_id'];
            $return_id = $return_product_detail['return_id'];
            $return_items_detail = $return_itemsObj->getList('*', $return_id, 0,-1);

            $return_data['status'] = $status;
            $return_data['return_id'] = $return_id;

            if(in_array($status, array('2','3'))){//审核中、接受申请
                if ($return_items_detail)
                foreach ($return_items_detail as $key=>$val){
                    $return_data['item_id'][$key] = $val['item_id'];
                    $return_data['effective'][$val['bn']] = $val['num'];
                    $return_data['bn'][$val['bn']] = $val['num'];
                }
                $returnObj->tosave($return_data,'','ecos.b2c');

            }elseif($status==4){//完成
                $totalmoney = 0;

                if ($return_items_detail)
                foreach ($return_items_detail as $key=>$val){
                    $return_data['branch_id'][$key] = $val['branch_id'];
                    $return_data['product_id'][$key] = $val['product_id'];
                    $return_data['item_id'][$key] = $val['item_id'];
                    $return_data['effective'][$key] = $val['num'];
                    $return_data['name'][$key] = $val['name'];
                    $return_data['bn'][$key] = $val['bn'];
                    $return_data['deal'.$key] = 1;#TODO:默认为退货，后期根据B2C修改
                }
                $return_data['totalmoney'] = $totalmoney;
                $return_data['tmoney'] = $totalmoney;
                $return_data['bmoney'] = '0.000';
                $return_data['memo'] = '';

                 /*统计此次请求对应货号退货数量累加*/
                $ret=array();
                $can_refund=array();
                foreach($return_data['bn'] as $k=>$v){
                   if(isset($ret[$v])){
                     $can_refund[$v][num]++;
                   }else{
                     $ret[$v] = $v;
                     $can_refund[$v]['num']=1;
                     $can_refund[$v]['effective']=$return_data['effective'][$k];
                   }
                   if($can_refund[$v]['effective']==0){
                          $rs['msg'] = '货号为['.$v.']没有可申请量，请选择拒绝操作,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                          $logInfo .= $msg;
                          $rs['logInfo'] = $logInfo;
                          return $rs;
                   }else if($can_refund[$v]['num']>$can_refund[$v]['effective']){
                          $rs['msg'] = '货号为['.$v.']大于可申请量，请选择拒绝操作,订单号:'.$order_bn.',售后申请单号:'.$return_bn;
                          $logInfo .= $msg;
                          $rs['logInfo'] = $logInfo;
                          return $rs;
                   }
                }
                $returnObj->saveinfo($return_data,'ecos.b2c');
            }else{
                $filter = array('return_id'=>$return_id);
                $data = array('status'=>$status);
                $returnObj->update($data, $filter);
            }

            //日志记录
            $rs['msg'] = '店铺('.$shop_name.')更新售后状态,订单:'.$order_bn.',售后单号:'.$return_bn;
            $rs['rsp'] = 'success';
            $logInfo .= $msg;
            $rs['logInfo'] = $logInfo;
            return $rs;

        }else{
            $rs['msg'] = 'Return_bn and Order_bn can not be empty';
            $logInfo .= $msg;
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }

    /**
     * 更新售后申请物流信息
     * @access public
     * @param array $return_sdf 售后物流信息
     */
    function logistics_update($return_sdf){

        /*Log info*/
        $log = &app::get('ome')->model('api_log');
        $logTitle = '前端店铺更新售后申请物流信息[售后单号：]' . $return_sdf['return_bn'];
        $logInfo = '前端店铺更新售后申请物流信息接口：<BR>';
        $logInfo .= '接收参数 $return_sdf 信息：' . var_export($return_sdf, true) . '<BR>';

        /*Log info*/

        $order_bn = $return_sdf['order_bn'];
        $return_bn = $return_sdf['return_bn'];
        //返回值
        $rs_data = array('tid'=>$order_bn,'aftersale_id'=>$return_bn,'retry'=>'false');
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        $shop_addon['bn'] = $return_bn;
        $shop_id = $return_sdf['shop_id'];
        $shop_name = $return_sdf['shop_name'];

        $returnObj = &app::get('ome')->model('return_product');
        #$oApi_log = &app::get('omeapilog')->model('api_log');
        $orderObj = &app::get('ome')->model('orders');
        $order_detail = $orderObj->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id');
        $return_product_detail = $returnObj->dump(array('return_bn'=>$return_bn,'order_id'=>$order_detail['order_id']),'return_id,return_bn,process_data');


        //判断售后申请单是否存在
        if(!$return_product_detail['return_id']){
            //日志记录
            $msg = '更新店铺('.$shop_name.')售后物流信息,售后单据号'.$return_bn.'不存在,订单号:'.$order_bn;
            $logInfo .= $msg;
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;

            return $rs;
        }
        //售后物流信息
        $logistics_info = json_decode($return_sdf['logistics_info'], true);
        $process_data = unserialize($return_product_detail['process_data']);
        if (!empty($logistics_info)){
            if ($process_data){
                foreach ($process_data as $prok=>$prov){
                    $process_data['shipcompany'] = $logistics_info['logi_company'];
                    $process_data['logino'] = $logistics_info['logi_no'];
                }
            }else{
                $process_data['shipcompany'] = $logistics_info['logi_company'];
                $process_data['logino'] = $logistics_info['logi_no'];
            }
        }
        if ($process_data){
            $update_data = array(
                'process_data' => serialize($process_data),
            );
            $update_filter = array('return_id'=>$return_product_detail['return_id']);
            $returnObj->update($update_data, $update_filter);
        }

        //日志记录
        $rs['rsp'] = 'success';
        $rs['logInfo'] = $logInfo;

        return $rs;
    }
}
?>