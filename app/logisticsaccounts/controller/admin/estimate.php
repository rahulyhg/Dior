<?php
class logisticsaccounts_ctl_admin_estimate extends desktop_controller{
    var $workground = 'logisticaccounts';
    var $name = '对账审核';

    function _views(){
        $sub_menu = $this->_views_estimate();
        return $sub_menu;
    }
    function _views_estimate(){
        
        $estimateObj = app::get('logisticsaccounts')->model("estimate");
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $base_filter=array();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $base_filter['branch_id'] = $branch_ids;
            }else{
                $base_filter['branch_id'] = 'false';
            }
        }
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false,'filter'=>array()),
            1 => array('label'=>app::get('base')->_('未对账'),'filter'=>array('status' =>'0'),'optional'=>false),
            2 => array(
                'label'=>app::get('base')->_('待记账'),
                'filter'=>array('status' =>'1'),
                'optional'=>false),
             3 => array(
                'label'=>app::get('base')->_('已记账'),
                'filter'=>array('status' =>'2'),
                'optional'=>false),
            4 => array(
                'label'=>app::get('base')->_('已审核'),
                'filter'=>array('status' =>'3'),
                'optional'=>false),
             5 => array(
                'label'=>app::get('base')->_('已关账'),
                'filter'=>array('status' =>'4'),
                'optional'=>false),

            );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'],$base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $estimateObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=logisticsaccounts&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    function index(){

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        #增加财务导出权限
        $is_export = kernel::single('desktop_user')->has_permission('finance_export');
        $params = array(
                            'title'=>'物流预估单',
                            'use_buildin_new_dialog' => false,
                            'use_buildin_set_tag'=>false,
                            'use_buildin_recycle'=>false,
                            'use_buildin_export'=> $is_export,
                            'use_buildin_filter'=>true,
                    );
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }
        $this->finder('logisticsaccounts_mdl_estimate',$params);
    }

    function import(){
        set_time_limit(0);

        $result = kernel::single('logisticsaccounts_estimate')->crontab_delivery();

    }

    /**
    *获取预估账单详情
    */
    function get_estimate(){
        $estimateObj = $this->app->model('estimate');
        $data = $_GET;
       
        if($data['action']=='get_logi_no'){
            $estimate = $estimateObj->getlist('logi_no',array('delivery_bn'=>$data['delivery_bn']),0,-1);
        }else if($data['action']=='get_money'){
            $estimate =$estimateObj->getlist('delivery_cost_expect,money_expect',array('delivery_bn'=>trim($data['delivery_bn']),'logi_no'=>trim($data['logi_no'])),0,-1);
            $estimate =$estimate[0];
        }
        if($estimate){
            echo json_encode($estimate);
        }
    }


}

?>