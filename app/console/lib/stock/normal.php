<?php
/**
* 普通货品库存处理类
* 
* 
*/
class console_stock_normal extends console_stock_stock{

    /**
    * 实际库存+
    * 仓库实际库存+ 货品实际库存+
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function storein($branch_id,$product_id,$nums){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        
        $status = false;
        if($this->chg_branch_storein($branch_id,$product_id,$nums)){
            if($this->chg_storein($product_id,$nums)){
                $status = true;
            }
        }
        return $status;
    }

    /**
    * 实际库存-
    * 仓库实际库存- 货品实际库存-
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @param Int $nums 数量
    * @return bool
    */
    public function storeout($branch_id,$product_id,$nums){
        if (empty($branch_id) || empty($product_id) || empty($nums)) return false;
        $status = false;
        if($this->chg_branch_storeout($branch_id,$product_id,$nums)){
            if($this->chg_storeout($product_id,$nums)){
                $status = true;
            }
        }
        return $status;
    }

    /**
    * 获取可售库存
    * 线上仓库库存-销售预占-组合预占-仓库冻结库存
    * @access public
    * @param Int $product_id 货品ID
    * @return Int
    */
    public function get_usable_sale_store($product_id){
        if (empty($product_id)) return 0;
        
        return $this->usable_sale_store($product_id);
    }

    /**
    * 获取可用库存
    * 库存-销售预占-组合预占-仓库冻结库存
    * @access public
    * @param Int $product_id 货品ID
    * @return Int
    */
    public function get_usable_store($product_id){
        if (empty($product_id)) return 0;
        
        return $this->usable_store($product_id);
    }

    /**
    * 获取所在仓库的可用库存
    * 库存-销售预占-组合预占-仓库冻结库存
    * @access public
    * @param Int $branch_id 仓库ID
    * @param Int $product_id 货品ID
    * @return Int
    */
    public function get_branch_usable_store($branch_id,$product_id){
        if (empty($branch_id) || empty($product_id)) return 0;
        
        return $this->branch_usable_store($branch_id,$product_id);
    }

}