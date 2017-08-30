<?php
class ome_finder_api_stock{

    var $detail_basic = '同步信息';
    function detail_basic($id){
        $render = app::get('ome')->render();
        $api_stock = &app::get('ome')->model('api_stock_log')->dump($id);
        /*$api_stock['memo'] = unserialize($api_stock['memo']);
        $api_stock['memo'][1]['list_quantity'] = stripslashes($api_stock['memo'][1]['list_quantity']);
        $api_stock['memo'][1]['list_quantity'] = str_replace($api_stock['product_bn'],'<font color=red style="background:#FF0">'.$api_stock['product_bn'].'</font>',$api_stock['memo'][1]['list_quantity']);
        $api_stock['params'] = json_decode($api_stock['params'],true);*/
        $api_stock['process_time'] = $api_stock['last_modified'] - $api_stock['createtime'];
        $api_stock['createtime'] = date('Y-m-d H:i:s',$api_stock['createtime']);
        $api_stock['last_modified'] = date('Y-m-d H:i:s',$api_stock['last_modified']);
        $api_stock['op_time'] = date('Y-m-d H:i:s',$api_stock['op_time']);

        $difftime = kernel::single('ome_func')->toTimeDiff(time(), $api_stock['process_time']);
        $html .= $difftime['d']?$difftime['d']. '天':'';
        $html .= $difftime['h']?$difftime['h'] . '小时':'';
        $html .= $difftime['m']?$difftime['m'] . '分':'';
        $api_stock['process_time'] = $html;

        $render->pagedata['api_stock'] = $api_stock;
       	$render->display('admin/api/stock_log.html');
    }
}
