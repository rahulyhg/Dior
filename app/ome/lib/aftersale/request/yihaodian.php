<?php
class ome_aftersale_request_yihaodian extends ome_aftersale_abstract{

    public function __construct()
    {
        $this->_render = app::get('ome')->render();
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
        $oReturn_product_yihaodian = &app::get('ome')->model ( 'return_product_yihaodian' );
        $return_product_yihaodian = $oReturn_product_yihaodian->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));
        $html = 'admin/return_product/plugin/edit_yihaodian.html';
        $this->_render->pagedata['return_product_yihaodian'] = $return_product_yihaodian;
        unset($return_product_yihaodian);
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
        $oReturn_product_yihaodian = &app::get('ome')->model ( 'return_product_yihaodian' );
        $data = array(
            'isdeliveryfee'   => $data['isdeliveryfee'],
            'sendbacktype'   => $data['sendbacktype'],
            'isdefaultcontactname'    => $data['isdefaultcontactname'],
            'contactname'   => $data['contactname'],
            'contactphone'  => $data['contactphone'],
            'sendbackaddress' => $data['sendbackaddress'],
            'return_bn'     => $data['return_bn'],
            'shop_id'       => $data['shop_id'],
            'return_id'     => $data['return_id'],
        );
        
        $oReturn_product_yihaodian->save($data);
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
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=yhd');
        }
        return $rs;
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
        $oReturn_product_yhd = &app::get('ome')->model ( 'return_product_yihaodian' );
        $return_product_yhd = $oReturn_product_yhd->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));
        
        $this->_render->pagedata['return_product_yhd'] = $return_product_yhd;
        
        $html = $this->_render->fetch('admin/return_product/plugin/detail_yihaodian.html');
        return $html;
    }

    function pre_save_return($data){
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];
        $oReturn = &app::get('ome')->model('return_product');
        $oReturn_yhd = &app::get('ome')->model('return_product_yihaodian');
        $return = $oReturn->dump($return_id,'*');
        $return_yhd = $oReturn_yhd->dump(array('return_id'=>$return_id));
        $isdefaultcontactname = $return_yhd['isdefaultcontactname'];
        

        if ($status == '3') {#
            if ($isdefaultcontactname=='0' && ($return_yhd['contactname']=='' || $return_yhd['contactphone']=='' || $return_yhd['sendbackaddress']=='')) {
                $rs['rsp'] = 'fail';
                $rs['msg'] = '退货信息必填!';
            }
       }
        return $rs;
    }

    
    /**
     * 转化的值.
     * @param  return_id
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    function choose_type_value($return_id)
    {
         $oReturn_yhd = &app::get('ome')->model('return_product_yihaodian');
         $return_yhd = $oReturn_yhd->dump(array('return_id'=>$return_id),'sendbacktype');
         if ($return_yhd['sendbacktype'] == '0') {
             $type_value='3';
             return $type_value;
         }
    }
}
?>