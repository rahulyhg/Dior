<?php
class taoexlib_finder_smslog{
	var $detail_edit = '详细列表';
    function detail_edit($id){
        $render = app::get('taoexlib')->render();
        $oItem = kernel::single("taoexlib_mdl_smslog");
        $items = $oItem->getList('*',
                     array('id' => $id), 0, 1);
        $render->pagedata['item'] = $items[0];
        $render->display('admin/smsdetail.html');
        //return 'detail';
    }	
}