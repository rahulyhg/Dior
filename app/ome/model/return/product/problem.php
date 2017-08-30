<?php

class ome_mdl_return_product_problem extends dbeav_model{

    
    /*
     * 获取类型名称
     */
    function getCatName($id=0){
        
       $filter = array('problem_id'=>$id);
       $catname = $this->dump($filter, 'problem_id,problem_name');
       return $catname['problem_name'];
    }
    
    /*
     * 获取类型
     */
   function getCatList($id=0){

       $catlist = $this->getList('problem_id,problem_name');
       if ($catlist){
	      return $catlist;
	   }else{
          return array();
	   }
   }

    
    
    /*
     * 仓库入库类型
     * 
     * @return array
     */
   function store_type(){
       $store_type = array('新仓','残仓','报废');
       return $store_type;
   }
 /*
     * 仓库入库类型
     * @param int $store_type
     * @return array
     */
   function get_store_type($store_type){
    
       $store = $this->store_type();
  
       return $store[$store_type];
   }
 
}
?>
