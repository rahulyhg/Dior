<?php
class console_finder_po{
    var $detail_basic = "采购单详情";
    var $detail_item = "采购单明细";
    var $detail_eo = "入库记录";
    function __construct($app)
    {
        $this->app = $app;

        if(!in_array($_GET['app'],array('console','wms')) || $_GET['ctl']=='admin_purchaselist'){
            
            unset($this->column_edit);
            
        }
    }
    var $addon_cols = "po_id,eo_status,statement,po_status,po_type,check_status,defective_status";
    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row){
        $find_id = $_GET['_finder']['finder_id'];
        $id = $row[$this->col_prefix.'po_id'];
        $stockset= &app::get('ome')->getConf('purchase.stock.stockset');
        $defective_status = $row[$this->col_prefix.'defective_status'];
        $eo_status = $row[$this->col_prefix.'eo_status'];
        $po_status = $row[$this->col_prefix.'po_status'];
        $check_status = $row[$this->col_prefix.'check_status'];
        $statement = $row[$this->col_prefix.'statement'];
        $user = kernel::single('desktop_user');
        $oPo_items = &app::get('purchase')->model('po_items');
        if($user->has_permission('console_purchase_do_eo')){
        $button2 = <<<EOF
        <a class="lnk" href="index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]=$id&find_id=$find_id" target="_blank">传统入库</a>
EOF;
        }
        if($user->has_permission('console_purchase_do_eo')){
         $button22 = <<<EOF
        <a class="lnk" href="index.php?app=wms&ctl=admin_eo&act=Barcode_stock&p[0]=$id&find_id=$find_id" target="_blank">条码入库</a>
EOF;
        }
        $button3 = <<<EOF
        <span class="lnk" onclick="new Dialog('index.php?app=wms&ctl=admin_purchase&act=cancel&p[0]=$id&p[1]=cancel',{title:'入库终止',width:500,height:250})">终止</span> 
EOF;
        $button3_disabled = <<<EOF
        <span class="c-disabled">终止</span> 
EOF;

        $button21 = <<<EOF
        <a class="lnk" href="index.php?app=wms&ctl=admin_purchase&act=printItem&p[0]=$id&p[1]=eo" target="_bank">打印</a> 
EOF;
        if ($_GET['act'] <> 'eoList' ){
            if($user->has_permission('console_purchase_check')) $button0 = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=check&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">审核</a> 
EOF;

            $button = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=editPo&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">编辑</a>
EOF;
            $button_disabled = <<<EOF
            <span class="c-disabled">编辑</span>
EOF;

            $button4 = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=printItem&p[0]=$id" target="_bank">打印</a> 
EOF;
            $button5 = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=addSame&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">新建相似</a>
EOF;
#查看是否有残损
            $button6 = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=difference&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">差异查看</a>
EOF;
            $button7 = <<<EOF
            <a class="lnk" href="index.php?app=console&ctl=admin_purchase&act=purchaseConfirm&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">残损确认</a>
EOF;
            $button8 = <<<EOF
             <span class="lnk" onclick="new Dialog('index.php?app=console&ctl=admin_purchase&act=cancel&p[0]=$id&p[1]=cancel&finder_id=$find_id',{title:'入库取消',width:500,height:250})">取消</span> 
EOF;
        
            $string = array();

            if($_GET['act']=='checklist'){
                if ($check_status == 1){
                    $string[]= $button0;
                }
            }else{
                if ($check_status== 1 && $po_status == 1){
                     $width += 50;
                    $string[]=$button0;
                }
                if ($statement != '3' && $check_status == 1 && $po_status == 1){
                    $width += 50;
                    $string[]= $button;
                }


                $string[]= $button4.'|'.$button5;
                if (($eo_status!='2' && $eo_status!='3') && $po_status=='1'){
                    $width += 50;
                    $string[]= $button8;
                }

            }
           
            #当出入库完成时
            if ($eo_status == '3'){
                $items = $oPo_items->db->select("SELECT * from sdb_purchase_po_items where po_id=".intval($id)." and (`in_num`!=`num` or `defective_num`!=0)");
               
                if($items){
                    $string[]= $button6;
                }
            
                $items1 = $oPo_items->db->select("SELECT * from sdb_purchase_po_items where po_id=".intval($id)." and `defective_num`!=0");
                if($items1 && $defective_status=='1'){
                    $string[]= $button7;
                }
            }
            

        }else{
            
            if( $eo_status <> '3' && $check_status==2 ){
                if($stockset=='true'){
                    
                    $string[]=$button22;
                }else{
                    $string[]=$button2;
                }
                $string[]=$button21;
            }
            if ($eo_status<3 ){
                $width += 50;
                $string[]= $button3;
            }
        }

        if($string)
        $string = '<span class="c-gray">'.implode('|',$string).'</span>';
        return $string;
    }
    
   function row_style($row){

        if($row[$this->col_prefix.'po_type'] == 'cash'){
            return "unconv";
        }
    }

    var $column_sku_num='sku种类';
    function column_sku_num($row){
        $id = $row[$this->col_prefix.'po_id'];

        $po = &app::get('purchase')->model('po')->getPoSkuTotalById($id);
        return $po['sku_number'];
    }

    var $column_total_number='采购总数量';
    function column_total_number($row){
        $id = $row[$this->col_prefix.'po_id'];
        $po = &app::get('purchase')->model('po')->getPoSkuTotalById($id);
        return $po['total'];
    }
    var $column_bn ='付款单 / 赊购单';
    function column_bn($row){
        $po_id = $row[$this->col_prefix.'po_id'];
        $po_bn = $row['po_bn'];#采购单编号
        $po_type =  $row['po_type'];#采购类型
        
        if($po_type == 'credit'){
            $sql = 'select cs_bn from sdb_purchase_credit_sheet where po_bn='."'$po_bn'";
            $credit = kernel::database()->select($sql);
            return $credit[0]['cs_bn']; 
        }elseif($po_type == 'cash'){
            $sql = 'select payment_bn from sdb_purchase_purchase_payments where po_id='."'$po_id'";
            $cash = kernel::database()->select($sql);
            return $cash[0]['payment_bn'];
        }
    }
    
    function detail_basic($po_id){
        $render = app::get('console')->render();
        $poObj = &app::get('purchase')->model('po');

        //备注追加
        if($_POST){
            $po['po_id'] = $_POST['id'];
            //取出原备注信息
            $oldmemo = $poObj->dump(array('po_id'=>$po['po_id']), 'memo');
            $oldmemo= unserialize($oldmemo['memo']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $memo[] = $v;
            }
            $op_name = kernel::single('desktop_user')->get_name();
            $newmemo = htmlspecialchars($_POST['memo']);
            $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);

            $po['memo'] = $memo;
            $poObj->save($po);
        }

        $eoObj = &app::get('purchase')->model('eo');
        $suObj = &app::get('purchase')->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $po = $poObj->dump($po_id,'*',array('po_items'=>array('*')));
        $eos = $eoObj->getList('eo_id,eo_bn,entry_time',array('po_id'=>$po_id),0,-1);
        $total_num=0;
        if ($po['po_items'])
        foreach($po['po_items'] as $k=>$v){
            $total_num+=$v['num'];
        }
        $su = $suObj->dump($po['supplier_id'],'name');
        $br = $brObj->dump($po['branch_id'], 'name');
        $po['branch_name']   = $br['name'];
        $po['supplier_name'] = $su['name'];
        $po['total_num']     = $total_num;
        $po['memo'] = unserialize($po['memo']);
        $render->pagedata['po'] = $po;
        $render->pagedata['eo'] = count($eos);
        $render->pagedata['emslist'] = $eos;
        $render->pagedata['count'] = count($eos);
        //物流费用计算
        $render->pagedata['delivery_cost'] = count($eos)*$po['delivery_cost'];
        return $render->fetch("admin/purchase/purchase_detail.html");
    }

    function detail_item($po_id){
        $render = app::get('console')->render();
        $poObj  = &app::get('purchase')->model('po');
        $pObj   = &app::get('ome')->model('products');
        $po = $poObj->dump($po_id,'po_id',array('po_items'=>array('*')));

        $render->pagedata['po_items'] = $po['po_items'];
        return $render->fetch("admin/purchase/purchase_item.html");
    }

    /*
     * 入库单记录
     */
    function detail_eo($po_id){
        $render = app::get('console')->render();
        $oBranch = &app::get('ome')->model('branch');
        $oSupplier = &app::get('purchase')->model('supplier');
        $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
        $eo_detail = $iostock_instance->getIsoList($po_id, ome_iostock::PURCH_STORAGE);
        $detail = array();
        if ($eo_detail)
        foreach ($eo_detail as $k => $v){
            $supplier = $oSupplier->supplier_detail($v['supplier_id'], 'name');
            $v['supplier_name'] = $supplier['name'];
            $branch = $oBranch->dump($v['branch_id'], 'name');
            $v['branch_name'] = $branch['name'];
            $detail[] = $v;
        }
        $render->pagedata['detail'] = $detail;
        $oReturnedPurchaes = &app::get('purchase')->model('returned_purchase');
        $oRefunds = &app::get('purchase')->model('purchase_refunds');
        $returned_detail = $oReturnedPurchaes->getList('*', array('object_id'=>$po_id,'rp_type'=>'po'), 0, -1);
        $cancel_detail = array();
        if ($returned_detail)
        foreach ($returned_detail as $k => $v){
            $supplier = $oSupplier->supplier_detail($v['supplier_id'], 'name');
            $v['supplier_name'] = $supplier['name'];
            $branch = $oBranch->dump($v['branch_id'], 'name');
            $v['branch_name'] = $branch['name'];
            $v['rp_type'] = $oRefunds->getReturnType($v['rp_type']);
            $v['po_type'] = $oRefunds->getPaymentType($v['po_type']);
            $cancel_detail[] = $v;
        }
        $render->pagedata['cancel_detail'] = $cancel_detail;

        return $render->fetch("admin/po/detail_eo.html");
    }

    /*
     * 入库终止单明细
    */
//    function detail_eo_cancel($po_id){
//        $render = app::get('purchase')->render();
//        $oReturnedPurchaes   = &app::get('purchase')->model('returned_purchase');
//        $oBranch   = &app::get('ome')->model('branch');
//        $oSupplier   = &app::get('purchase')->model('supplier');
//        $oRefunds  = &app::get('purchase')->model('purchase_refunds');
//        $returned_detail = $oReturnedPurchaes->getList('*', array('object_id'=>$po_id,'rp_type'=>'po'), 0, -1);
//        $detail = array();
//        if ($returned_detail)
//        foreach ($returned_detail as $k => $v){
//            $supplier = $oSupplier->supplier_detail($v['supplier_id'], 'name');
//            $v['supplier_name'] = $supplier['name'];
//            $branch = $oBranch->dump($v['branch_id'], 'name');
//            $v['branch_name'] = $branch['name'];
//            $v['rp_type'] = $oRefunds->getReturnType($v['rp_type']);
//            $v['po_type'] = $oRefunds->getPaymentType($v['po_type']);
//            $detail[] = $v;
//        }
//
//        $render->pagedata['detail'] = $detail;
//        return $render->fetch("admin/eo/eo_cancel_list.html");
//    }
   
   
}