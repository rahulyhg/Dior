<?php
class ome_finder_tbgift_goods{
    var $column_control = '操作';
    var $column_control_width = "150";
    function column_control($row){
        #获取赠品的商品类型
        $gift_goods = &app::get('ome')->model('tbgift_goods');
        $type = $gift_goods->dump($row['goods_id'],'goods_type');
        $find_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_preprocess_tbgift&act=edit&p[0]='.$row['goods_id'].'&p[1]='.$type['goods_type'].'&finder_id='.$find_id.'&_finder[finder_id]='.$find_id.'" target="_blank">编辑</a>';
    }

    function detail_basic($gift_id){
        $render = app::get('ome')->render();
        $gift_product = &app::get('ome')->model('tbgift_product');
        $data = $gift_product->getAllProduct($gift_id);
        $render->pagedata['gift_data'] = $data;
        return $render->fetch('admin/preprocess/tbgift/detail_basic.html');
    }


}
?>
