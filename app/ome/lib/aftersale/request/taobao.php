<?php
class ome_aftersale_request_taobao extends ome_aftersale_abstract{
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }
    static private $cs_status = array(
        1 => '不需客服介入',
        2 => '需要客服介入',
        3 => '客服已经介入',
        4 => '客服初审完成',
        5 => '客服主管复审失败',
        6 => '客服处理完成',    
    );
    static private $advance_status = array(
        0=>'未申请状态',
        1 => '退款先行垫付申请中 ',
        2 => '退款先行垫付，垫付完成 ',
        3 => '退款先行垫付，卖家拒绝收货',
        4 => ' 退款先行垫付，垫付关闭',
        5 => '退款先行垫付，垫付分账成功',    
    );
    static private $good_status = array(
        'BUYER_NOT_RECEIVED'=>'买家未收到货',
        'BUYER_RECEIVED'=>'买家已收到货',
    );
    
    /** 
     * 退款申请详情扩展.
     * 
     *   
     * 
     * @author 
     */
    function refund_detail($refundinfo)
    {
        $apply_id = $refundinfo['apply_id'];
        $shop_id = $refundinfo['shop_id'];
        $apply_bn = $refundinfo['shop_id'];
        $oRefund_taobao = &app::get('ome')->model ( 'refund_apply_taobao' );
        $refund_taobao = $oRefund_taobao->dump(array('apply_id'=>$apply_id,'shop_id'=>$shop_id));
        $refund_taobao['cs_status'] = self::$cs_status[$refund_taobao['cs_status']];
        $refund_taobao['advance_status'] = self::$advance_status[$refund_taobao['advance_status']];
        $refund_taobao['good_status'] = self::$good_status[$refund_taobao['good_status']];
        if ($refund_taobao) {
            $refundinfo = array_merge($refundinfo,$refund_taobao);
        }
       
        if ($refundinfo['message_text']) {
            $refundinfo['message_text'] = unserialize($refundinfo['message_text']);
        }
        if ($refundinfo['refuse_memo']) {
            $refundinfo['refuse_memo'] = unserialize($refundinfo['refuse_memo']);
        }
        if ($refundinfo['online_memo']) {
            $refundinfo['online_memo'] = unserialize($refundinfo['online_memo']);
        }
        $product_data = $refundinfo['product_data'];
        if ($product_data) {
            $product_data = unserialize($product_data);
        }
        $refundinfo['product_data'] = $product_data;
        $this->_render->pagedata['refundinfo'] = $refundinfo;
        unset($refundinfo);
        $html = $this->_render->fetch('admin/refund/plugin/refund_taobao.html');
        return $html;
    }

    function pre_save_refund($apply_id,$data)
    {
        $rs = array('rsp'=>'succ','msg'=>'成功','data'=>'');
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '3') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,3,'sync');
            
            return $result;
            
        }
        
        
    }

    
    /**
     * 保存退款时按钮直接跳转还是dialog
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function refund_button($apply_id,$status)
    {
        $rs = array('rsp'=>'default','msg'=>'成功','data'=>'');
        if ($status == '3') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_refund_apply&act=upload_refuse_message&p[0]='.$apply_id.'&p[1]=taobao');
        }
        return $rs;
    }

    /**
     * 售后申请编辑前扩展
     * @param   array    $returninfo
     * @return  
     * @access  public
     * @author 
     */
    function pre_return_product_edit($returninfo)
    {
        $return_id = $returninfo['return_id'];
        $shop_id = $returninfo['shop_id'];
        $oReturn_product_tmall = &app::get('ome')->model ( 'return_product_taobao' );
        $return_product_taobao = $oReturn_product_tmall->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));
        $oReturn_address = &app::get('ome')->model ( 'return_address' );
        $return_address = $oReturn_address->getDefaultAddress($shop_id);
        if ($return_product_taobao['reship_addr']=='' && $return_product_taobao['reship_zip']=='' && $return_product_taobao['reship_name']=='' && $return_product_taobao['reship_phone']=='' && $return_product_taobao['reship_mobile']=='') {
            $return_product_taobao['reship_addr'] = $return_address['address'];
            $return_product_taobao['reship_zip'] = $return_address['zip_code'];
            $return_product_taobao['reship_name'] = $return_address['contact_name'];
            $return_product_taobao['reship_phone'] = $return_address['tel'];
            $return_product_taobao['reship_mobile'] = $return_address['mobile_phone'];
        }
        $html = 'admin/return_product/plugin/edit_taobao.html';
        $this->_render->pagedata['return_product_taobao'] = $return_product_taobao;
        unset($return_product_taobao);
        $html = $this->_render->fetch($html);
        return $html;
    }

    /**
     * 售后申请编辑后扩展
     * @param   array    data
     * @return  
     * @access  public
     * @author 
     */
    function return_product_edit_after($data)
    {
        #更新附加表操作
        $oReturn_product_tmall = &app::get('ome')->model ( 'return_product_taobao' );
        $data = array(
           
            'reship_addr'   => $data['reship_addr'],
            'reship_zip'    => $data['reship_zip'],
            'reship_name'   => $data['reship_name'],
            'reship_phone'  => $data['reship_phone'],
            'reship_mobile' => $data['reship_mobile'],
            'return_bn'     => $data['return_bn'],
            'shop_id'       => $data['shop_id'],
            'return_id'     => $data['return_id'],
        );

        $oReturn_product_tmall->save($data);
    }

    /**
     * 售后服务详情查看页扩展
     * @param   array    $returninfo    
     * @return  html
     * @access  public
     * @author 
     */
    public function return_product_detail($returninfo)
    {
        $return_id = $returninfo['return_id'];
        $shop_id = $returninfo['shop_id'];
        $oReturn_product_taobao = &app::get('ome')->model ( 'return_product_taobao' );
        $return_product_taobao = $oReturn_product_taobao->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));
        $ship_area = implode(':',$return_product_taobao['reship_area']);
        $return_product_taobao['reship_area'] = $ship_area[1];
        $return_product_taobao['cs_status'] = self::$cs_status[$return_product_taobao['cs_status']];
        $return_product_taobao['advance_status'] = self::$advance_status[$return_product_taobao['advance_status']];
        $return_product_taobao['good_status'] = self::$good_status[$return_product_taobao['good_status']];
        $return_product_taobao['has_good_return'] = $return_product_taobao['has_good_return']=='true'? '是':'否';
        if ($return_product_taobao['online_memo']) {
            $return_product_taobao['online_memo'] = unserialize($return_product_taobao['online_memo']);
        }
        $refuse_memo = $return_product_taobao['refuse_memo'];
        if ($refuse_memo) {
            $return_product_taobao['refuse_memo'] = unserialize($return_product_taobao['refuse_memo']);
        }
        $this->_render->pagedata['return_product_taobao'] = $return_product_taobao;
        
        $html = $this->_render->fetch('admin/return_product/plugin/detail_taobao.html');
        return $html;
    }

    /**
     * 售后拒绝时弹出的页面.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '5') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=taobao');
        }
        return $rs;
    }

    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function reship_edit($returninfo)
    {
        $oReturn_product_taobao = &app::get('ome')->model ( 'return_product_taobao' );
        $returninfo = $oReturn_product_taobao->dump(array('return_id'=>$returninfo['return_id']));
         
        if ($returninfo['online_memo']) {
            $returninfo['online_memo'] = unserialize($returninfo['online_memo']);
        }
        
        $this->_render->pagedata['returninfo'] = $returninfo;
        $html = $this->_render->fetch('admin/return_product/plugin/reship_taobao.html');
        return $html;
    }

   /**
     * 售后保存前的扩展
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function pre_save_return($data)
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];
        $oReturn_address = &app::get('ome')->model('return_address');
        $oReturn_taobao = &app::get('ome')->model('return_product_taobao');
        $return_address = $oReturn_address->dump(array('shop_id'=>$data['shop_id'],'cancel_def'=>'true'));
        if ($status == '3') {#接受申请
            
            $return_taobao = $oReturn_taobao->dump(array('return_id'=>$return_id));
            if (($return_taobao['reship_addr'] == '' || $return_taobao['reship_zip'] == '' || $return_taobao['reship_name'] == '' || $return_taobao['reship_phone'] == '' || $return_taobao['reship_mobile'] == '') ) {
                $rs['rsp'] = 'fail';
                $rs['msg'] = '请编辑设置卖家退货地址!';
            }
        }
        return $rs;
    }
}
?>