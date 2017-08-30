<?php
class console_finder_inventory_apply{
   
    var $detail_item = "详情";

   
    
    function detail_item($apply_id){
        $render = &app::get('console')->render();
        $inv_aiObj = &app::get('console')->model('inventory_apply_items');
        
        $count = $inv_aiObj->count(array('inventory_apply_id'=>$apply_id));
        if ($count > 20){
            $render->pagedata['many'] = 'true';
            $rows = $inv_aiObj->getList('*', array('inventory_apply_id'=>$apply_id), 0, 20);
        }else {
            $rows = $inv_aiObj->getList('*', array('inventory_apply_id'=>$apply_id), 0, -1);
        }
        $render->pagedata['apply_id'] = $apply_id;
        $render->pagedata['rows'] = $rows;
        return $render->fetch("admin/inventory/apply/item.html");
    }

    var $column_operation = '操作';
    var $column_operation_width = 70;
    public function column_operation($row){
        $apply_id = $row['inventory_apply_id'];
        $inv_aObj = &app::get('console')->model('inventory_apply');
        $info = $inv_aObj->dump($apply_id);
        $id = $info['inventory_apply_id'];
        $fid = $_GET['_finder']['finder_id'];
        
        if($info['status'] == 'unconfirmed' && $info['append'] == 'true'){
            $return = ' '.sprintf('<a href="javascript:if (confirm(\'确认要关闭可追加状态？\')){window.open(\'index.php?app=console&ctl=admin_inventory_apply&act=do_confirm&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">确认</a>',$id,$fid);
            $return .= ' | '.sprintf('<a href="javascript:if (confirm(\'确认要关闭吗？\')){W.page(\'index.php?app=console&ctl=admin_inventory_apply&act=do_close&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">关闭</a>',$id,$fid);
            //$return .= '<a href="index.php?app=console&ctl=admin_inventory_apply&act=do_confirm&p[0]='.$info['inventory_apply_id'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="_blank">确认</a>';
        }elseif ($info['status'] == 'unconfirmed'){
            $return = ' <a href="index.php?app=console&ctl=admin_inventory_apply&act=do_confirm&p[0]='.$id.'&finder_id='.$fid.'" target="_blank">确认</a>';
            $return .= ' | '.sprintf('<a href="javascript:if (confirm(\'确认要关闭吗？\')){W.page(\'index.php?app=console&ctl=admin_inventory_apply&act=do_close&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">关闭</a>',$id,$fid);
        }

        return $return;
    }
    
}
?>
