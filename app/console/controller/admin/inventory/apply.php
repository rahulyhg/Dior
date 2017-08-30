<?php
class console_ctl_admin_inventory_apply extends desktop_controller{
    var $workground = "console_center";
    function index(){
        $params = array(
            'title'=>'盘点申请',
            'use_buildin_recycle'=>false,
            'orderBy' => 'inventory_date desc',
        );

       $this->finder('console_mdl_inventory_apply',$params);
    }

    function do_confirm($apply_id=0){
        if ($apply_id){
            $msg = '';
            $inv_aObj = &app::get('console')->model('inventory_apply');
            $inv_aObj->close_append($apply_id);//关闭追加
            $sdf = $inv_aObj->dump($apply_id, '*', array('inventory_apply_items'=>'*'));
            if ($sdf['status'] == 'confirmed' || $sdf['status'] == 'closed'){
                $msg .= "盘点流水单已确认或已取消<br/>";
            }else {
                $branch = $inv_aObj->get_branch_by_wms($sdf['wms_id']);

                if (count($branch) > 1){
                    //选择仓库或上传文件
                    $this->do_choice($sdf, $branch);
                    exit();
                }
                $branch_bn = $branch[0]['branch_bn'];
                //判断数据正确性
                if ($sdf['inventory_apply_items'])
                foreach ($sdf['inventory_apply_items'] as $item){
                    if (!$inv_aObj->exist_product($item['product_id'])){
                        $m1 .= $item['bn']."<br/>";
                    }elseif (!$inv_aObj->exist_branch($item['product_id'], $branch_bn)){
                        $m1 .= $item['bn']."<br/>";
                    }
                    if (!$inv_aObj->exist_num($item['product_id'], $item['quantity'])){
                        $m2 .= $item['bn']."<br/>";
                    }elseif (!$inv_aObj->exist_branch_num($item['product_id'], $branch_bn, $item['quantity'])){
                        $m2 .= $item['bn']."<br/>";
                    }
                }
            }
            if ($m1 || $m2 || $msg){
                if ($m1){
                    $msg .= "盘点结果确认失败！<br/>";
                    $msg .= "下列商品在仓库不存在：<br/>";
                    $msg .= $m1;
                }
                if ($m2){
                    $msg .= "下列商品库存不足，无法盘亏：<br/>";
                    $msg .= $m2;
                }
                $this->pagedata['flag'] = 'false';
                $this->pagedata['msg'] = $msg;
            }else{
                $this->pagedata['flag'] = 'true';
                $this->pagedata['apply'] = $sdf;
                $this->pagedata['branch'] = $branch_bn;
            }
            $this->pagedata['finder_id']  = $_GET['finder_id'];
            $this->do_view();
        }
        exit('错误路径');
    }

    function do_close($apply_id){
        $inv_aObj = &app::get('console')->model('inventory_apply');
        $inv_aObj->close_append($apply_id);//关闭追加
        $this->begin("index.php?app=console&ctl=admin_inventory_apply&act=index");
        $sdf = $inv_aObj->dump($apply_id);
        if ($sdf['status'] == 'confirmed' || $sdf['status'] == 'closed') $this->end(false, '盘点流水已确认或取消');
        $rs = $inv_aObj->update(array('status'=>'closed','process_date'=>time()),array('inventory_apply_id'=>$apply_id));
        if ($rs) $this->end(true, '处理成功');
        $this->end(false, '处理失败');
    }

    function finish_confirm(){
        $this->begin("index.php?app=console&ctl=admin_inventory_apply&act=index");
        $inv_aObj = &app::get('console')->model('inventory_apply');
        $invObj = &app::get('console')->model('inventory');

        $apply_id = $_POST['apply_id'];
        $branch_bn = $_POST['branch_bn'];
        if (!$branch_bn) $this->end(false, '无仓库数据');
        if (!$apply_id) $this->end(false, '错误路径');

        $oBranch = app::get('ome')->model('branch');
        $branch = $oBranch->dump(array('branch_bn' => $branch_bn), 'branch_id,branch_bn');//主仓

        $sdf = $inv_aObj->dump($apply_id, '*', array('inventory_apply_items'=>'*'));
        $sdf['damaged_branch_id'] = empty($branch_damaged) ? 0 : $branch_damaged['branch_id'];
        $damagedSdf = $sdf;

        if ($sdf['status'] == 'confirmed' || $sdf['status'] == 'closed') $this->end(false, '盘点流水已确认或取消');
        if ($sdf['inventory_apply_items']){
            foreach ($sdf['inventory_apply_items'] as $key => $item){
                if (!$inv_aObj->exist_branch($item['product_id'], $branch_bn)) $this->end(false, $item['bn'].':货品不在仓库中');

                if (!$inv_aObj->exist_branch_num($item['product_id'], $branch_bn, $item['normal_num'])) $this->end(false, '货品仓库库存不足');

                $sdf['inventory_apply_items'][$key]['normal_num'] = $item['normal_num'];
                $sdf['inventory_apply_items'][$key]['bntype'] = 'normal';
                    //add by lymz at 2012-3-12 13:47:56 对不良货品做操作
                if (is_numeric($item['defective_num']) && $item['defective_num'] != 0) {
                    $branch_damaged = $oBranch->dump(array('parent_id' => $branch['branch_id'], 'type' => 'damaged'), 'branch_id,branch_bn');//主仓对应的残仓

                    if (empty($branch_damaged))
                        $this->end(false, $item['bn'] . ':有不良品，但未设置主仓对应的残仓');
                    else if ($item['defective_num'] < 0 && !$inv_aObj->exist_branch_num($item['product_id'], $branch_damaged['branch_bn'], $item['defective_num']))
                        $this->end(false, $item['bn'] . ':有不良品，但残仓库存不足');

                    $damagedSdf['inventory_apply_items'][$key]['normal_num'] = $item['defective_num'];
                    $damagedSdf['inventory_apply_items'][$key]['bntype'] = 'defective';
                } else
                    unset($damagedSdf['inventory_apply_items'][$key]);
            }

            //出入库 TODO 创建 盘点单
                //良品
            $rs1 = $invObj->do_save($sdf, $branch_bn);
                //不良品
            if (count($damagedSdf['inventory_apply_items']) > 0)

                $rs2 = $invObj->do_save($damagedSdf, $branch_damaged['branch_bn']);
            else
                $rs2 = true;
            if ($rs1 && $rs2){
                $this->end(true, '处理成功');
            }
            $this->end(false, '处理失败');
        }
    }

    function do_view(){
        $this->singlepage("admin/inventory/view.html");
    }

    function do_choice($applySdf, $branch){
        $this->pagedata['finder_id']  = $_GET['finder_id'];
        $this->pagedata['apply'] = $applySdf;
        $this->pagedata['branch'] = $branch;

        $this->singlepage("admin/inventory/choice.html");
    }

    function finish_choice(){
        if ($_POST){
            $post = $_POST;
            if (!$post['branch']) return false;
            $_POST['apply_id'] = $post['inventory_apply_id'];
            $_POST['branch_bn'] = $post['branch'];
            $this->finish_confirm();
        }
    }

    function view_item(){
        $base_filter = array();
        if ($_GET['apply_id']){
            $base_filter = array('inventory_apply_id'=>$_GET['apply_id']);
        }
        $params = array(
            'title'=>'盘点申请详情',
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'base_filter'=>$base_filter,
        );
        $this->finder('console_mdl_inventory_apply_items',$params);
    }
}