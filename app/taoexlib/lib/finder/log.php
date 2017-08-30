<?php
class taoexlib_finder_log{
	var $detail_edit = '详细列表';
    function detail_edit($id){
        $render = app::get('taoexlib')->render();
        $oItem = kernel::single("taoexlib_mdl_log");
        $items = $oItem->getList('*',
                     array('id' => $id), 0, 1);
        $render->pagedata['item'] = $items[0];
        $render->display('admin/logdetail.html');
        //return 'detail';
    }	
}