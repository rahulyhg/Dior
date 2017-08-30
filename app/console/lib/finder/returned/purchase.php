<?php
class console_finder_returned_purchase{
    function __construct($app)
    {
        $this->app = $app;
        
        if(!in_array($_GET['app'],array('console','wms')) || $_GET['ctl']=='admin_returned_purchaselist'){
            
            unset($this->column_edit);
            
        }
    }
    var $column_bn_width = "140";
    var $addon_cols = "rp_id,return_status,check_status";
    function column_bn($rows){
        $rp_id = $rows[$this->col_prefix.'rp_id'];
        $oRefund = &app::get('purchase')->model('returned_purchase');
        $oPo = &app::get('purchase')->model('po');
        $oEo = &app::get('purchase')->model('eo');
        $oRefuned_purchase = $oRefund->dump($rp_id, 'object_id,rp_type');
        if ($oRefuned_purchase['rp_type']=='po'){
            $bndetail = $oPo->dump($oRefuned_purchase['object_id'], 'po_bn');
            $bn = $bndetail['po_bn'];
        }elseif ($oRefuned_purchase['rp_type']=='eo'){
            $bndetail = $oEo->dump($oRefuned_purchase['object_id'], 'eo_bn');
            $bn = $bndetail['eo_bn'];
        }else $bn = '-';

        return $bn;
    }
   
    var $column_edit = "操作";
    var $column_edit_width = "120";
    function column_edit($row){
       
        $find_id = $_GET['_finder']['finder_id'];
        $id = $row[$this->col_prefix.'rp_id'];
        $return_status = $row[$this->col_prefix.'return_status'];
        $check_status = $row[$this->col_prefix.'check_status'];
        $act = empty($_GET['act'])?'index':$_GET['act'];
        $oReturn_items = &app::get('purchase')->model('returned_purchase_items');
        $button = array();
        $button_disabled = <<<EOF
        <span class="c-disabled">编辑</span>
EOF;
         $button[]= <<<EOF
         <a class="lnk" href="index.php?app=console&ctl=admin_returned_purchase&act=printItem&p[0]=$id" target="_bank">打印</a>
EOF;
if($act == 'oList' && $row['return_status']==1){
            $button[]= <<<EOF
            <a class="lnk" href="index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">出库</a> 
EOF;
            $button[]= <<<EOF
            <span class="lnk" onclick="new Dialog('index.php?app=wms&ctl=admin_returned_purchase&act=cancel&p[0]=$id',{title:'拒绝出库',width:500,height:250})">拒绝</span>
EOF;
        }
        if($act == 'index'){
            if($check_status==1 && $return_status==1){
                $button[]= <<<EOF
                <a class="lnk" href="index.php?app=console&ctl=admin_returned_purchase&act=editReturn&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">编辑</a>
EOF;
            }else{
               $button[]= <<<EOF
               <span class="c-disabled">编辑</span>
EOF;
            }
            if ($check_status == '1' && $return_status==1) {
            $button[]= <<<EOF
            
            <a href="index.php?app=console&ctl=admin_returned_purchase&act=check&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">审核</a>
EOF;
            }
            
           if ($return_status == '2'){
            $items = $oReturn_items->db->select("SELECT * from sdb_purchase_returned_purchase_items where rp_id=".intval($id)." and (`out_num`!=`num`)");
            if ($items){
            $button[]= <<<EOF
            <a href="index.php?app=console&ctl=admin_returned_purchase&act=difference&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id" target="_blank">差异查看</a>
EOF;
            }
           }
           if($return_status=='1'){
            
            $button[]= <<<EOF
            <span class="lnk" onclick="new Dialog('index.php?app=console&ctl=admin_returned_purchase&act=cancel&p[0]=$id&_finder[finder_id]=$find_id&finder_id=$find_id',{title:'取消出库',width:500,height:250})">取消</span>
EOF;
            }
        }

        $button = '<span class="c-gray">'.implode('|',$button).'</span>';
        return $button;
    }
    
    var $detail_basic = "采购退货单详情";
    var $detail_item = "采购退货单明细";
   

    function detail_basic($rp_id){
        $render = app::get('console')->render();
        $returnedObj = &app::get('purchase')->model('returned_purchase');
        $eoObj = &app::get('purchase')->model('eo');
        $SupplierObj = &app::get('purchase')->model('supplier');
        $branchObj = &app::get('ome')->model('branch');

        //备注追加
        if($_POST){
            $rp['rp_id'] = $_POST['rp_id'];
            //取出原备注信息
            $oldmemo = $returnedObj->dump(array('rp_id'=>$rp['rp_id']), 'memo');
            $oldmemo= unserialize($oldmemo['memo']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $memo[] = $v;
            }
            $op_name = kernel::single('desktop_user')->get_name();
	        $newmemo =  htmlspecialchars($_POST['memo']);
	        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);

            $rp['memo'] = $memo;
            $returnedObj->save($rp);
        }

        $rp = $returnedObj->dump($rp_id, '*');
        $eo = $eoObj->dump($rp['object_id'], 'eo_bn');
        $rp['eo_bn'] = $eo['eo_bn'];
        $supplier = $SupplierObj->dump($rp['supplier_id'], 'name');
        $rp['supplier_name'] = $supplier['name'];
        $branch = $branchObj->dump($rp['branch_id'], 'name');
        $rp['branch_name'] = $branch['name'];

        $rp['memo'] = unserialize(($rp['memo']));
        $render->pagedata['rp'] = $rp;
        return $render->fetch("admin/returned/purchase/base_detail.html");
    }

    function detail_item($rp_id){
        $render = app::get('console')->render();
        $poObj  = &app::get('purchase')->model('returned_purchase');
        $pObj   = &app::get('ome')->model('products');
        $po = $poObj->dump($rp_id,'rp_id',array('returned_purchase_items'=>array('*')));
        
        $render->pagedata['po_items'] = $po['returned_purchase_items'];
        return $render->fetch("admin/returned/purchase/purchase_item.html");
    }

}