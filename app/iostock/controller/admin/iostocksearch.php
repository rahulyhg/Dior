<?php
class iostock_ctl_admin_iostocksearch extends desktop_controller{
    public function index(){
        $is_export = kernel::single('desktop_user')->has_permission('iostock_search_export');#增加出入库导出权限
        $this->title = '出入库查询';
        //仓库编号，仓库名称，出入库单号，出入库类型，原始单据号，供应商（供应商/用户名/公司名称），货号，货品名称（确认信息），出入库数量，出入库价格，经手人，出入库时间，操作员，备注
        //出入库时间-倒序，出入库类型-正序，货号-正序，
        $params = array(
            'title'=>$this->title,
            'finder_cols' => 'column_branch_id,branch_id,iostock_bn,type_id,orginal_bn,column_supplier,bn,column_name,nums,iostock_price,oper,create_time,operator,memo',
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' => 'create_time desc,iostock_id desc,type_id asc,bn asc',
        );
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);

            $panel->setId('iostock_finder_top');

            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('iostock_mdl_iostocksearch', $params);

        }
        $this->finder('iostock_mdl_iostocksearch',$params);
    }

    
    /**
     * 重新算一下库存
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function stock()
    {
        if ($_POST) {
            $bn = $_POST['bn'];
            $branch_id = $_POST['branch_id'];
            $productObj = app::get('ome')->model('products');
            $product = $productObj->dump(array('bn'=>$bn),'*');
            if (empty($product)) {
                echo "<script>alert('货号不存在请确认');this.history.go(-1);</script>";
                exit;
            }
            $stock = kernel::single('iostock_stock')->get_stock_list($bn,$branch_id);
            
            $this->pagedata['data'] = $_POST;
            $this->pagedata['stock'] = $stock;

        }
        

        $branchObj = app::get('ome')->model('branch');
        $branchList = $branchObj->Get_branchlist();

        $this->pagedata['branchList'] = $branchList;
        $this->page("stock.html");
    }

    
    


}