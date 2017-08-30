<?php
class ome_finder_reship{
    function __construct($app)
    {
        $this->app = $app;
        if($_GET['app'] == 'console' ){
            unset($this->column_edit);
        }
        
    }
    var $addon_cols = 'need_sv,is_check,archive,source,order_id';

    var $detail_basic = "退货单详情";
    

    function detail_basic($reship_id){
        $render = app::get('ome')->render();
        $oProduct = &app::get('ome')->model('products');
        $oReship = &app::get('ome')->model('reship');
        $detail = $oReship->getCheckinfo($reship_id);
        
        $oDesktop          = &app::get('desktop')->model('users');
        $desktop_detail    = $oDesktop->dump(array('user_id'=>$detail['op_id']), 'name');
        $detail['op_name'] = $desktop_detail['name'];

        if($detail['is_check'] == '3') $detail['is_check'] = '1';

        $cols = $oReship->_columns();
        $detail['is_check'] = $cols['is_check']['type'][$detail['is_check']];

        $reason = unserialize($detail['reason']);
        $detail['check_memo'] = $reason['check'];

        # 售后问题类型        
        if ($detail['problem_id']) {
            $detail['problem_name'] = app::get('ome')->model('return_product_problem')->getCatName($detail['problem_id']);
        }

        $render->pagedata['detail'] = $detail;
        $reship_item = $oReship->getItemList($reship_id);
        $recover = array();
        foreach ($reship_item as $key => $value) {
            $spec_info = $oProduct->dump($value['product_id'],'spec_info');
            $reship_item[$key]['spec_info'] = $spec_info['spec_info'];
            $recover[$value['return_type']][] = $reship_item[$key];
        }

        $render->pagedata['items'] = $recover;
        return $render->fetch('admin/reship/detail.html');
    }

    var $detail_log = "操作日志";
    function detail_log($reship_id){
        $render = app::get('ome')->render();
        $oOperation_log = app::get('ome')->model('operation_log');
        $render->pagedata['log'] = $oOperation_log->read_log(array('obj_type'=>'reship@ome','obj_id'=>$reship_id),0,20,'log_id desc');
        return $render->fetch('admin/reship/detail_log.html');
    }

  
    var $detail_acceptreturned = "收货记录";
    function detail_acceptreturned($reship_id){
        $render = &app::get('ome')->render();
        $Oreship = &app::get('ome')->model('reship');
        $Oreturn_process = &app::get('ome')->model('return_process');
        $return = $Oreturn_process->getList('process_data',array('reship_id'=>$reship_id));
        $process_data = unserialize($return[0]['process_data']);
        $process_data['shipdaofu'] = $process_data['shipdaofu']==1?'是':'否';
        if($process_data['shipcompany']){
            $oDc = &app::get('ome')->model('dly_corp');
            $dc_data = $oDc->dump($process_data['shipcompany']);
            $process_data['shipcompany'] = !empty($dc_data['name'])?$dc_data['name']:'';
        }
        $Oreason = $Oreship->dump(array('reship_id'=>$reship_id),'reason');
        $Oreason['reason'] = unserialize($Oreason['reason']);
        $p = strpos($Oreason['reason'],'#收货原因#');

        if($p!==false){
           $reason = str_replace('#收货原因#','',$Oreason['reason']);
           $process_data['refuse_memo'] = $reason;
        }

        $render->pagedata['detail'] = $process_data;
        return $render->fetch('admin/reship/detail_acceptreturned.html');
    }


    var $detail_returnedsv = "质检记录";
    function detail_returnedsv($reship_id){
        $render = &app::get('ome')->render();
        $Oreship = &app::get('ome')->model('reship');
        $Oreturn_process = &app::get('ome')->model('return_process');
        $oProblem = &app::get('ome')->model('return_product_problem');
        $oBranch = &app::get('ome')->model('branch');

        $return = $Oreturn_process->getList('process_data,memo',array('reship_id'=>$reship_id));

        $Oreturn_process_items = &app::get('ome')->model('return_process_items');
        $process_items = $Oreturn_process_items->getList('bn,memo,store_type,branch_id,num',array('is_check'=>'true','reship_id'=>$reship_id));

        $process_data = unserialize($return[0]['process_data']);
        $process_data['shipdaofu'] = $process_data['shipdaofu']==1?'是':'否';
        $Oreason = $Oreship->dump(array('reship_id'=>$reship_id),'reason,is_check,need_sv');

        $process_data['reason'] = unserialize($Oreason['reason']);

        foreach($process_items as $k=>$v){
           $process_items[$k]['store_type'] = $oProblem->get_store_type($process_items[$k]['store_type']);
           $branch = $oBranch->db->selectrow("SELECT name from sdb_ome_branch WHERE branch_id=".$process_items[$k]['branch_id']);

           $process_items[$k]['branch_id'] = $branch['name'];
        }

        $render->pagedata['memo'] = $return[0]['memo'];
        $render->pagedata['process_items'] = $process_items;
        $render->pagedata['detail'] = $process_data;

        $s = kernel::single('ome_reship')->is_precheck_reship($Oreason['is_check'],$Oreason['need_sv']);
        if ($s) {
            $render->pagedata['memo'] = '质检异常';
        }

        return $render->fetch('admin/reship/detail_returnsv.html');
    }


    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row){
        $oReship = &app::get('ome')->model('reship');
        $reship_items= $oReship->dump($row['reship_id'],'reship_bn');
        $is_check = $row[$this->col_prefix.'is_check'];
		if ($is_check == '11' || $is_check=='9'){
			$edit_title = '最终收货';
		}else{
			$edit_title = '编辑';
		}
        $precheck = kernel::single('ome_reship')->is_precheck_reship($row[$this->col_prefix.'is_check'],$row[$this->col_prefix.'need_sv']);

        $check = '<a target="dialog::{width:1200,height:546,title:\'审核退换货单号:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=check&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">'.($precheck ? '最终收货' : '审核').'</a>  ';
        
        $anti_check = '  <a target="dialog::{width:250,height:100,title:\'反审核退换货单号:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=anti_check&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">反审核</a>  ';

        $edit ='  <a target="dialog::{width:1200,height:546,title:\'编辑退换货单:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=edit&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">'.$edit_title.'</a>  ';

        $accept_returned = '  <a target="dialog::{width:1200,height:546,title:\'收货退换货单:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=accept_returned&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">收货</a>  ';

        $cancel = '  <a target="dialog::{width:280,height:100,title:\'取消退换货单号:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=do_cancel&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">取消</a>  ';

        $doback = '  <a target="dialog::{width:280,height:100,title:\'退换货单号:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_rchange&act=do_back&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">退回</a>';

        $quality_check = '  <a target="dialog::{width:1200,height:546,title:\'质检退换货单:'.$reship_items['reship_bn'].'\'}" href="index.php?app=ome&ctl=admin_return_sv&act=edit&p[0]='.$row['reship_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">收货/质检</a>';

       $permissions = array(
            'check'         => 'aftersale_rchange_check',
            'anti_check'    => 'aftersale_rchange_recheck',
            'edit'          => 'aftersale_rchange_edit',
            'cancel'        => 'aftersale_rchange_refuse',
            'doback'        => 'aftersale_rchange_back',
            'quality_check' => 'aftersale_rchange_sv'
       );
        if(!kernel::single('desktop_user')->is_super()){
            $returnLib = kernel::single('ome_return');
            foreach ($permissions as $key=>$permission) {
                $has_permission = $returnLib->chkground('aftersale_center','',$permission);
                // $has_permission = kernel::single('desktop_user')->has_permission($permission);
                if (!$has_permission) {
                    $$key = '';
                }
            }
        }

        switch($row[$this->col_prefix.'is_check']){
            case '0':
			case '12':
               $cols = $check.$edit.$cancel;
            break;
            case '1':
              if($_GET['flt'] == 'process_list'){
                $cols = $quality_check;
              }else{
                $cols = '';
              }
            break;
            case '2':
               $cols = $check.$edit;
            break;
            case '3':
			case '13':
              if($_GET['flt'] == 'process_list'){
                $cols = $quality_check;
              }else{
                $cols = '';
              }
            break;
            case '10':
            case '4':
            
             
              if($_GET['flt'] == 'process_list'){
                $cols = '';
              }else{
                $cols = $anti_check.$cancel;
              }
            break;
            case '5':
            case '6':
            case '7':
			
                $cols = '';
            break;
            case '8':
               $cols = <<<EOF
            <a href="index.php?app=ome&ctl=admin_return_sv&act=recheck&p[0]={$row['reship_id']}&finder_id={$_GET['_finder']['finder_id']}">重新质检</a>
EOF;
            break;
            case '9':
              if($_GET['flt'] == 'process_list'){
                $cols = '';
              }else{
                $cols = $edit.$cancel.$doback;
              }
            break;
           
           
            case '11':
              
                $cols = $edit.$cancel;
                break;
            default:
               $cols = 'wrong';
        }
        return $cols;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function row_style($row) 
    {
        $s = kernel::single('ome_reship')->is_precheck_reship($row['is_check'],$row[$this->col_prefix.'need_sv']);
        return $s ? 'highlight-row' : '';
    }


    var $column_order_id='订单号';
    var $column_order_id_width='100';
    function column_order_id($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        $source = $row[$this->col_prefix . 'source'];
        $order_id = $row[$this->col_prefix . 'order_id'];
        if ($archive == '1' || in_array($source,array('archive'))) {
            $orderObj = app::get('archive')->model('orders');
            
        }else{
            $orderObj = app::get('ome')->model('orders');
            
        }
        $filter = array('order_id'=>$order_id);
        $order = $orderObj->dump($filter,'order_bn');

        return $order['order_bn'];
    }
}

?>