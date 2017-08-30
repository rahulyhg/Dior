<?php
/**
* 库存基类
* 
* 
*/
class console_stock_stock{

    function __construct(){
        $this->pObj = kernel::single('console_stock');
    }

   

    /**
    * 组合预占+
    * @access public
    * @param Int $product_id 普通货品ID
    * @param Int $nums 预占数量
    * @return bool
    */
    public function chg_combine_freeze($product_id,$nums){
        if (empty($product_id) || empty($nums)) return false;

        $pModel = &app::get('ome')->model('products');
        if($pModel->combine_freeze($product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 组合预占-
    * @access public
    * @param Int $product_id 基础货品ID
    * @param Int $nums 预占数量
    * @return bool
    */
    public function chg_combine_unfreeze($product_id,$nums){
        if (empty($product_id) || empty($nums)) return false;

        $pModel = &app::get('ome')->model('products');
        if($pModel->combine_unfreeze($product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 货品实际库存+
    * @access public
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_storein($product_id,$nums=''){
        if (empty($product_id) || empty($nums)) return false;
        
        $pModel = &app::get('ome')->model('products');
        if($pModel->storein($product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 货品实际库存-
    * @access public
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_storeout($product_id,$nums=''){
        if (empty($product_id) || empty($nums)) return false;
        
        $pModel = &app::get('ome')->model('products');
        if($pModel->storeout($product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 仓库冻结+
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_branch_freeze($branch_id,$product_id,$nums){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        
        $bpModel = &app::get('ome')->model('branch_product');
        if($bpModel->freez($branch_id,$product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 仓库冻结-
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_branch_unfreeze($branch_id,$product_id,$nums){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        
        $bpModel = &app::get('ome')->model('branch_product');
        if($bpModel->unfreez($branch_id,$product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 仓库实际库存+
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_branch_storein($branch_id,$product_id,$nums=''){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        
        $bpModel = &app::get('ome')->model('branch_product');
        if($bpModel->storein($branch_id,$product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 仓库实际库存-
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function chg_branch_storeout($branch_id,$product_id,$nums=''){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        
        $bpModel = &app::get('ome')->model('branch_product');
        if($bpModel->storeout($branch_id,$product_id,$nums)){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 可售库存=线上仓库的库存-销售预占-组合预占-线上仓库的仓库冻结
    * @access public
    * @param Int $product_id 货品ID
    * @return bool
    */
    public function usable_sale_store($product_id){
        if (empty($product_id)) return 0;
        
        
        $pModel = &app::get('ome')->model('products');
        $branch_usable_store = $this->get_usable_store($product_id,'online');
        $p = $pModel->getRow($product_id,'store_freeze');
        $usable_store = $branch_usable_store - $p['store_freeze'] ;
        
        return $usable_store;
    }

    /**
    * 可用库存=货品实际库存 - 销售预占 - 组合预占 - 仓库冻结
    * @access public
    * @param Int $product_id 货品ID
    * @return bool
    */
    public function usable_store($product_id){
        if (empty($product_id)) return 0;
        
        #$bpModel = &app::get('ome')->model('branch_product');
        $pModel = &app::get('ome')->model('products');

        $bp_freeze = $this->total_store_freeze($product_id);
        $p = $pModel->getRow($product_id,'store,store_freeze,combination_freeze');
        $usable_store = $p['store'] - $p['store_freeze'] - $p['combination_freeze'] - $bp_freeze;

        return $usable_store;
    }

    /**
    * 获取组合可售库存=组合库存-组合销售预占
    * @access public
    * @param Int $product_id 货品ID
    * @return bool
    */
    public function combine_usable_sale_store($product_id){
        if (empty($product_id)) return 0;
        
        $pModel = &app::get('ome')->model('products');
        $p = $pModel->getRow($product_id,'store,store_freeze');
        $usable_store = $p['store'] - $p['store_freeze'];

        return $usable_store;
    }

    /**
    * 获取仓库可用库存=仓库实际库存 - 仓库冻结库存
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @return bool
    */
    public function branch_usable_store($branch_id,$product_id=''){
        if (empty($branch_id) || empty($product_id)) return 0;
        
        
        $usable_store = $this->get_available_store($branch_id,$product_id);
        
        return $usable_store;
    }

    /**
     *查看单仓库中的可用库存
     *
     **/
    function get_available_store($branch_id,$product_id){
        $bpModel = &app::get('ome')->model('branch_product');
        $branch = $bpModel->getList('store,store_freeze',array('product_id'=>$product_id,'branch_id'=>$branch_id),0,1);
        
        return $branch[0]['store'] - $branch[0]['store_freeze'];
    }

    /**
     * 获取货品总可用库存：所有线上仓库
     * @param Int $product_id 货品ID
     * @param Bool $attr 仓库属性,online线下 offline线下 空为所有
     **/
    function get_usable_store($product_id,$attr=''){
        if (empty($product_id)) return NULL;
    
        return $this->_usable_store($product_id,$attr);
    }

    private function _usable_store($product_id='',$attr=''){
        $filter = array('product_id'=>$product_id);
        $attr_where = '';
        if ($attr){
            $attr = $attr == 'online' ? 'true' : 'false';
            $attr_where = ' AND b.attr=\''.$attr.'\' ';
        }
        $sql = sprintf('SELECT bp.store,bp.store_freeze FROM sdb_ome_branch AS b,sdb_ome_branch_product AS bp WHERE b.branch_id=bp.branch_id AND bp.product_id=\'%s\' %s ',$product_id,$attr_where);
        $branch = $this->db->select($sql);
        
        $usable_store = $store = $store_freeze = 0;
        if ($branch){
            foreach ($branch as $b){
                $store += $b['store'];
                $store_freeze += $b['store_freeze'];
            }
            $usable_store = $store - $store_freeze;
        }
        return $usable_store;
    }
    
    /**
     * 获取货品在所有仓库中的冻结数
     *
     **/
    function total_store_freeze($product_id){
        if(empty($product_id)) return 0;
        
        $sql = sprintf('SELECT sum(store_freeze) AS store_freeze FROM `sdb_ome_branch_product` WHERE product_id=\'%s\'',$product_id);
        $bp = $this->db->selectrow($sql);
        
        $store_freeze = isset($bp['store_freeze']) ? $bp['store_freeze'] : '0';
        return $store_freeze;
    }
}