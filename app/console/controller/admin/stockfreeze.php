<?php
class console_ctl_admin_stockfreeze extends desktop_controller{
    var $name = "库存冻结";
    var $workground = "console_center";

    function index(){
        
        
        $this->display('admin/stockfreeze/product.html');
    }

    

    
    /**
     * 显示所有货品冻结.
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function showall()
    {
        //获取所有有差异的货品

        $diff_data = kernel::single('console_storefreeze')->get_all_diff();
       
        $productObj = &app::get('ome')->model('products');
        $storefreezeLib = kernel::single('console_storefreeze');
        foreach($diff_data as $dk=>$data){
            $product_id = $dk;
            $rs[$product_id]['bn'] = $data['bn'];
            $rs[$product_id]['local_product_store_freeze'] = $data['local_product_store_freeze'];
            $rs[$product_id]['real_product_freeze'] = $data['real_product_freeze'];
            $rs[$product_id]['real_branch_freeze'] = $data['real_branch_freeze'];
            $rs[$product_id]['local_branch_freeze'] = $data['local_branch_freeze'];

            $ids[] = $product_id;
        }
        
        $this->pagedata['data'] = $rs;
        $this->pagedata['ids'] =serialize($ids);
        $this->pagedata['show'] = 'all';
        $this->display('admin/stockfreeze/product.html');
    }

    
    /**
     * 修正冻结库存.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function fix_freeze_store()
    {
        $id = $_POST['product_id'];
        //$product_id = unserialize($id) ? unserialize($id) : (array)$id;
        $rs = kernel::single('console_storefreeze')->fix_freeze_store($id);
        echo json_encode($rs);
    }

    
    /**
     * 修复冻结库存.
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function repare_freeze_store()
    {
        $product_ids = $_POST['product_ids'];
        
        $product_ids = unserialize($product_ids) ;
       
        foreach ($product_ids  as $product_id ) {
            kernel::single('console_storefreeze')->fix_freeze_store($product_id);
        }
        $rs = 'success';
        echo json_encode($rs);
    }
}
?>