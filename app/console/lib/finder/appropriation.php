<?php
class console_finder_appropriation{
    function __construct($app)
    {
        $this->app = $app;
        
    }
    
    var $column_confirm='操作';
    var $column_confirm_width = "120";
    function column_confirm($row){
        $appropriation_type = &app::get('ome')->getConf('taoguanallocate.appropriation_type');
        if (!$appropriation_type) $appropriation_type = 'directly';
        $finder_id = $_GET['_finder']['finder_id'];

        $id = $row['appropriation_id'];
        $iostockorder = &app::get('taoguaniostockorder')->model('iso')->dump(array('original_id'=>$id,'type_id'=>40),'confirm');
        $button = <<<EOF
        &nbsp;&nbsp;<a href="index.php?app=console&ctl=admin_appropriation&act=printAppropriation&p[0]=$id" target="_bank" class="lnk">打印</a> &nbsp;&nbsp;
EOF;
    if($appropriation_type!='directly' && $iostockorder['confirm']=='N'){
//当调拔出入库时 入库单未入库

        $button.= <<<EOF
        &nbsp;&nbsp;<a href="index.php?app=console&ctl=admin_appropriation&act=deleteAppropriation&p[0]=$id&finder_id=$finder_id" class="lnk">删除</a> &nbsp;&nbsp;
EOF;
}
        return $button;
    }


    public $detail_items = '调拨单明细';
    public function detail_items($appropriation_id)
    {
        $render = $this->app->render();

        $items = &app::get('taoguanallocate')->model('appropriation_items')->select()->columns('*')->where('appropriation_id=?',$appropriation_id)->instance()->fetch_all();
        foreach ($items as $key => $item) {
            $items[$key]['spec_info'] = &$spec[$item['product_id']];
            $items[$key]['barcode'] = &$barcode[$item['product_id']];
            $items[$key]['frome_branch_store'] = $item['from_branch_num'];
            $items[$key]['to_branch_store'] = $item['to_branch_num'];

            $product_id[] = $item['product_id'];
        }

        if ($items) {

            $productList = app::get('ome')->model('products')->getList('product_id,spec_info,barcode',array('product_id'=>$product_id));
            foreach ($productList as $product) {
                $spec[$product['product_id']] = $product['spec_info'];
                $barcode[$product['product_id']] = $product['barcode'];
            }

            /*
            $branch_products = app::get('ome')->model('branch_product')->getList('product_id,branch_id,store',array('product_id'=>$product_id,'branch_id'=>array($items[0]['from_branch_id'],$items[0]['to_branch_id'])));
            foreach ($branch_products as $key => $value) {
                $frome_branch_store[$value['branch_id']][$value['product_id']] = $value['store'];
                $to_branch_store[$value['branch_id']][$value['product_id']] = $value['store'];
            }*/
        }

        if ($items[0]) {
            $from_branch_id = $items[0]['from_branch_id']; $to_branch_id = $items[0]['to_branch_id'];

            $branches = app::get('ome')->model('branch')->getList('name,branch_id',array('branch_id'=>array($from_branch_id,$to_branch_id)));

            foreach ($branches as $key => $branch) {
                if ($from_branch_id == $branch['branch_id']) {
                    $render->pagedata['from_branch_name'] = $branch['name'];
                }

                if ($to_branch_id == $branch['branch_id']) {
                    $render->pagedata['to_branch_name'] = $branch['name'];
                }
            }
        }

        $oAppropriation = &app::get('taoguanallocate')->model("appropriation");
        $appropriation_info = $oAppropriation->dump(array('appropriation_id'=>$appropriation_id),'*');
        $render->pagedata['appropriation_info'] = $appropriation_info;
        $render->pagedata['items'] = $items;
        return $render->fetch('admin/appropriation/detail/items.html');
    }

}
?>