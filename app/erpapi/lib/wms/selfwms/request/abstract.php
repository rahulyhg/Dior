<?php
/**
 * 供应商推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_wms_selfwms_request_abstract 
{
    private $title = '';
    private $original_bn = '';

    protected $__channelObj;

    public $__apilog;

    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }

    /**
     * 成功输出
     *
     * @return void
     * @author 
     **/
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'success', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * 失败输出
     *
     * @return void
     * @author 
     **/
    final public function error($msg, $msgcode, $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * 失败日志
     *
     * @return void
     * @author 
     **/
    final public function save_api_fail($obj_bn,$obj_type,$method,$err_msg)
    {
        $failApiModel = app::get('erpapi')->model('api_fail');
        $fail = $failApiModel->dump(array('obj_bn'=>$obj_bn,'obj_type'=>$obj_type));

        $fail_params = array(
            'obj_bn'     => $obj_bn,
            'obj_type'   => $obj_type,
            'method'     => $method,
            'err_msg'    => $err_msg,
            'fail_times' => ((int)$fail['fail_times'] + 1),
            'status'     => 'fail',
        );
        if ($fail) $fail_params['id'] = $fail['id'];

        $failApiModel->save($fail_params);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function request($wms_class,$wms_method,$wms_params)
    {   
        try {
            $wmsObj = kernel::single($wms_class);
            if (!method_exists($wmsObj, $wms_method)) throw new Exception("{$wms_method}  NOT FOUND");

            $rs = $wmsObj->$wms_method($wms_params);

            // 记日志
            $apilogModel = app::get('ome')->model('api_log');
            $log_id = $apilogModel->gen_id();

            if (is_array($wms_params['items']) && $wms_params['items']) $wms_params['items'] = json_encode($wms_params['items']);

            $status = $rs['rsp'] == 'succ' ? 'success' : 'fail';
            $logsdf = array(
                'log_id'        => $log_id,
                'task_name'     => $this->title,
                'status'        => $status,
                'worker'        => $wms_class.':'.$wms_method,
                'params'        => serialize(array($wms_method, $wms_params, array())),
                'msg'           => var_export($rs,true),
                'log_type'      => '',
                'api_type'      => 'request',
                'memo'          => '',
                'original_bn'   => $this->original_bn,
                'createtime'    => time(),
                'last_modified' => time(),
                'msg_id'        => '',
            );
            $apilogModel->insert($logsdf);


            if ($rs['rsp'] != 'succ') return $this->error($rs['msg'],$rs['msg_code']);


            $msg = isset($rs['msg']) ? $rs['msg'] : '';
            $msg_code = $rs['msg_code'];
            $data = isset($rs['data']) ? $rs['data'] : array();

            return $this->succ($msg,$msg_code,$data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());   
        }
    }

    /**
     * 发货单创建
     *
     * @return void
     * @author 
     **/
    public function delivery_create($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '发货单添加';
        $this->original_bn = $sdf['outer_delivery_bn'];

        $rs = $this->request('wms_event_receive_delivery','create',$sdf);

        if ($rs['rsp'] == 'fail') {
            $this->save_api_fail($sdf['outer_delivery_bn'],'delivery',WMS_SALEORDER_CREATE,$rs['msg']);
        }

        return $rs;
    }

    /**
     * 发货单暂停
     *
     * @return void
     * @author 
     **/
    public function delivery_pause($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '发货单暂停';
        $this->original_bn = $sdf['outer_delivery_bn'];

        return $this->request('wms_event_receive_delivery','pause',$sdf);
    }

    /**
     * 发货单暂停恢复
     *
     * @return void
     * @author 
     **/
    public function delivery_renew($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '发货单恢复';
        $this->original_bn = $sdf['outer_delivery_bn'];

        return $this->request('wms_event_receive_delivery','renew',$sdf);
    }

    /**
     * 发货单取消
     *
     * @return void
     * @author 
     **/
    public function delivery_cancel($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '发货单撤销';
        $this->original_bn = $sdf['outer_delivery_bn'];

        return $this->request('wms_event_receive_delivery','cancel',$sdf);
    }

    /**
     * 商品添加
     *
     * @return void
     * @author 
     **/
    public function goods_add($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '添加物料';

        return $this->request('wms_event_receive_goods','create',$sdf);
    }

    /**
     * 商品编辑
     *
     * @return void
     * @author 
     **/
    public function goods_update($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '更新物料';

        return $this->request('wms_event_receive_goods','updateStatus',$sdf);
    }

    /**
     * 退货单创建
     *
     * @return void
     * @author 
     **/
    public function reship_create($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '退货单创建';
        $this->original_bn = $sdf['reship_bn'];

        return $this->request('wms_event_receive_reship','create',$sdf);
    } 

    /**
     * 退货单创建取消
     *
     * @return void
     * @author 
     **/
    public function reship_cancel($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '退货单取消';
        $this->original_bn = $sdf['reship_bn'];

        return $this->request('wms_event_receive_reship','updateStatus',$sdf);
    } 

    /**
     * 转储单创建
     *
     * @return void
     * @author 
     **/
    public function stockdump_create($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '转储单创建';
        $this->original_bn = $sdf['stockdump_bn'];

        return $this->request('wms_event_receive_stockdump','create',$sdf);
    } 

    /**
     * 转储单取消
     *
     * @return void
     * @author 
     **/
    public function stockdump_cancel($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '转储单取消';
        $this->original_bn = $sdf['stockdump_bn'];

        return $this->request('wms_event_receive_stockdump','updateStatus',$sdf);
    } 

    /**
     * 入库单创建
     *
     * @return void
     * @author 
     **/
    public function stockin_create($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '入库单创建';
        $this->original_bn = $sdf['io_bn'];

        $type = $sdf['io_type'];

        switch($type){
            case 'PURCHASE':#采购
                $wms_class = 'wms_event_receive_purchase';
                $wms_method = 'create';
                break;

            case 'ALLCOATE':#调拨
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'increate';
                break;
            case 'DIRECT':#直接
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'create';
                break;
            default:#其它 OTHER
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'create';
                break;
        }

        return $this->request($wms_class,$wms_method,$sdf);
    } 

    /**
     * 入库单取消
     *
     * @return void
     * @author 
     **/
    public function stockin_cancel($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '入库单取消';
        $this->original_bn = $sdf['io_bn'];

        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE':#采购
                $wms_class = 'wms_event_receive_purchase';
                $wms_method = 'updateStatus';
                break;
            case 'ALLCOATE':#调拨
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'updateStatus';
                break;
            case 'DIRECT':#直接
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'updateStatus';
                break;
            default:#其它
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'updateStatus';
                break;
        }

        return $this->request($wms_class, $wms_method, $sdf);
    } 

    /**
     * 出库单创建
     *
     * @return void
     * @author 
     **/
    public function stockout_create($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '出库单创建';
        $this->original_bn = $sdf['io_bn'];

        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE_RETURN':#采购退货
                $wms_class = 'wms_event_receive_purchasereturn';
                $wms_method = 'create';
                break;
            
            case 'ALLCOATE':#调拨出库
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'outcreate';
                break;
            case 'DIRECT':#直接出库
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'create';
                break;
            default:#其它出库 OTHER
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'create';
                break;
        }  

        return $this->request($wms_class,$wms_method,$sdf);
    } 

    /**
     * 出库单取消
     *
     * @return void
     * @author 
     **/
    public function stockout_cancel($sdf){
        $this->title = $this->__channelObj->wms['channel_name'] . '出库单取消';
        $this->original_bn = $sdf['io_bn'];

        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE_RETURN':#采购退货
                $wms_class = 'wms_event_receive_purchasereturn';
                $wms_method = 'updateStatus';
                break;
            case 'ALLCOATE':#调拨出库
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'updateStatus';
                break;
            case 'DIRECT':#直接出库
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'updateStatus';
                break;
            default:#其它出库 OTHER
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'updateStatus';
                break;
        }
        return $this->request($wms_class,$wms_method,$sdf);
    } 

    public function supplier_create($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function branch_getlist($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function logistics_getlist($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function delivery_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function reship_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function stockin_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }

    public function stockout_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }
}