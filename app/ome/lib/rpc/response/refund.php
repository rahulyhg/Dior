<?php
/**
 * 前端店铺退款业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_refund extends ome_rpc_response
{

    /**
     * 添加退款单
     * @access public
     * @param array $refund_sdf 退款单数据
     * @param object $responseObj 框架API接口实例化对象
     * @return array 退款单主键ID array('refund_id'=>'退款单主键ID')
     *///20120706189369
    function add($refund_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'refund','add',$refund_sdf);

        $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade.refund',$refund_sdf['order_bn']);
        $data = array('tid'=>$rs['data']['tid'],'refund_id'=>$rs['data']['refund_id'],'retry'=>$rs['data']['retry']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $this->send_user_error(app::get('base')->_($rs['msg']), $data);
            exit;
        }
        exit;

    }

    /**
     * 更新退款单状态
     * @access public
     * @param array $status_sdf 退款单状态数据
     * @param object $responseObj 框架API接口实例化对象
     */
    function status_update($status_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');   

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'refund','status_update',$status_sdf);

        $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade.refund',$refund_sdf['order_bn']);

        $data = array('tid'=>$rs['data']['tid'],'refund_id'=>$rs['data']['refund_id'],'retry'=>$rs['data']['retry']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $this->send_user_error(app::get('base')->_($rs['msg']), $data);
            exit;
        }

    }



}