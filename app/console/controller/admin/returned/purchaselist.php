<?php
/**
 * 采购退货单
 * @author sunjing@shopex.cn
 *
 */
class console_ctl_admin_returned_purchaselist extends desktop_controller{
    var $workground = "console_center";
    function index($rp_type=NULL, $io=null){
        //列表标题及过滤条件
        switch($rp_type)
        {
            case 'po':
                $sub_title = "入库取消单";
                break;
            case 'eo':
                $sub_title = "采购退货单列表";
                break;
            default:
                $sub_title = "退货单";
        }
        $params = array(
            'title'=>$sub_title,

            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'finder_cols'=>'column_edit,supplier_id,name,product_cost,delivery_cost,amount,logi_no,return_status,operator',
            'orderBy' => 'returned_time desc'
        );
        if($rp_type){
            $params['base_filter']['rp_type'] = $rp_type;
        }

        $this->finder('purchase_mdl_returned_purchase', $params);
    }


}


?>