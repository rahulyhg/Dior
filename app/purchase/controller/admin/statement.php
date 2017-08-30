<?php
/**
 * 结算清单控制器
 * @author ome team
 * @copyright www.shopex.cn 2010.4.27
 */
class purchase_ctl_admin_statement extends desktop_controller{
    
    var $name = "结算单明细";
    var $workground = "purchase_manager";

    /*
     * 结算清单列表显示
     */    
    function index($supplier_id=null, $workground=null){

        if (isset($supplier_id) and !$supplier_id){
            $supplier_id = '-1';
            $this->workground = 'purchase_manager';
        }else{
            if ($workground=='invoice'){
                $this->workground = 'invoice_center';
            }elseif (isset($supplier_id)){
                $this->workground = 'purchase_manager';
            }
            else{
                $this->workground = 'invoice_center';
            }
        }
        $params = array(
                        'title'=>'结算单明细',
                        'actions'=>array(
                            //array('label'=>'打印选中','submit'=>'index.php?app=purchase&ctl=admin_statement&act=prints','target'=>'_blank'),
                            array('label'=>'打印全部','href'=>'index.php?app=purchase&ctl=admin_statement&act=statement_print','target'=>'_blank'),
                        ),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'base_filter' => array('supplier_id'=>$supplier_id)
                    );
        $this->finder('purchase_mdl_statement', $params);
        
    }
    
    /*
     * 结算清单明细
     */    
    function statement_detail($supplier_id=null){

        if ($supplier_id) $base_filter = array("supplier_id"=>$supplier_id);
        $params = array(
                        'title'=>'结算明细',
                        'actions'=>array(
                            array('label'=>'打印','href'=>'index.php?app=purchase&ctl=admin_statement&act=statement_print&statement_id='.$supplier_id,'target'=>'_blank'),
                        ),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'base_filter' => $base_filter
                    );
        $this->finder('purchase_mdl_statement', $params);
        
    }
    
    /*
     * 财务结算单合计
     * @package statement_counter
     * @param int
     */
    public function statement_counter($supplier_id=null)
    {
        $oStatement = &$this->app->model("statement");
        $result = $oStatement->statement_counter($supplier_id);

        $this->pagedata['results'] = $result;
        $this->display('admin/purchase/statement/statement_counter.html');
    }
    
    /*
     * 结算单打印 
     * @package stetement_print
     */
    function statement_print()
    {
        $oStatement = &$this->app->model("statement");
        $id = intval($_GET['statement_id']);
        if ($id){
            if (!is_numeric($id)) die('para is undefined');
            $filter = $id;
        }else{
            $filter = $_POST['statement_id'];
        }
        $filter = $filter ? array('statement_id' =>$filter) : array(); 
        $supplier_list = $oStatement->statement_print_do($filter);
        
        $this->pagedata['supplier_list'] = $supplier_list;
        $this->pagedata['base_dir'] = kernel::base_url();
         //类型类型
        $this->pagedata['statement_type'] = $oStatement->getStatementType();
		$this->display("admin/print.html");
        $this->display('admin/purchase/statement/statement_print.html');
    }
    
    /*
     * 采购结算统计表
     */    
    public function ClearingTables($action=null, $begin_date=null, $end_date=null, $supplier_id=null){

        $this->workground = 'invoice_center';
        $_POST['begin_date'] = $_POST['begin_date'] ? $_POST['begin_date'] : $begin_date;
        $_POST['end_date'] = $_POST['end_date'] ? $_POST['end_date'] : $end_date;
        if ($_POST['begin_date']) {
            $_POST['begin_date'] = strtotime($_POST['begin_date']);
            $_POST['begin_date'] = date("Y-m-d",$_POST['begin_date']);
        }
        if ($_POST['end_date']) {
            $_POST['end_date'] = strtotime($_POST['end_date']);
            $_POST['end_date'] = date("Y-m-d",$_POST['end_date']);
        }
        $_POST['supplier'] = $_POST['supplier'] ? $_POST['supplier'] : $supplier_id;
        $oStatement = &$this->app->model("statement");
        $clearingtable = $oStatement->GetClearingTables($_POST);
        //供应商
        $oSupplier = $this->app->model('supplier');
        $supplier_list = $oSupplier->getList('supplier_id,name,bn', '', 0,-1, 'supplier_id asc');
        $this->pagedata['supplier'] = $supplier_list;
        
        #统计日期
        $clearingtable['begin_date'] = $clearingtable['begin_date'] ? $clearingtable['begin_date'] : $clearingtable['start_statetime'];
        $clearingtable['end_date'] = $clearingtable['end_date'] ? $clearingtable['end_date'] : date("Y-m-d",time());
        if (!$clearingtable['begin_date']){
            $clearingtable['begin_date'] = date("Y-m-d",time());
        }
        $this->pagedata['clearingtable'] = $clearingtable;
        
        if ($action=='print'){#打印
		   $this->display("admin/print.html");
           $this->display('admin/purchase/statement/clearingtables_print.html');
        }else{#显示
            $this->page('admin/purchase/statement/clearingtables.html');
        }
    }
    
}
?>