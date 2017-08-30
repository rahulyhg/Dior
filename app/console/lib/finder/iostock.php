<?php
class console_finder_iostock{
    var $detail_basic = '详情';

    public function detail_basic($bn_branch)
    {
        // 取货号和库ID
        $arr        = explode('*$**',$bn_branch);
        $bn         = $arr[1];
        $branch_id  = $arr[0];

        // 整理查询条件
        $time_from  = $_GET['time_from'];
        $time_to    = $_GET['time_to'];
        $filter     = array('time_from' => $time_from, 'time_to' => $time_to);

        // 查询
        $mels       = app::get('console')->model('interface_iostocksearchs');
        $row        = $mels->details($bn, $branch_id, $filter);
        /*//获取sku，出库数，入库数，仓库名，时间，出入库类型，价格
        $sql = "select oi.original_bn,oi.iostock_price,oi.nums,oi.type_id,oi.create_time,oi.branch_id,oit.type_name as type_name,op.goods_id,og.bn as og_sku from ".kernel::database()->prefix."ome_iostock as oi left join ".kernel::database()->prefix."ome_products as op on oi.bn=op.bn left join ".kernel::database()->prefix."ome_goods  as og on og.goods_id=op.goods_id left join ".kernel::database()->prefix."ome_iostock_type as oit on oit.type_id = oi.type_id where oi.bn='$bn'";
        $row = kernel::database()->select($sql);
        foreach($row as $key=>$r)
        {
            $row[$key]['create_time'] = date("Y-m-d H:i:s",$r['create_time']);
        }
        */
        $render = app::get('console')->render();
        $render->pagedata['rows'] = $row;

        return $render->display('admin/detail_goods.html');
    }
}