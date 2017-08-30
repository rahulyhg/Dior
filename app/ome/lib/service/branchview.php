<?php
/**
 * 显示选择仓库
 * 仅用于选择仓库的界面
 * @author Chirs.Zhang
 * @package ome_service_branchview
 * @copyright www.shopex.cn 2010.12.30
 *
 */
class ome_service_branchview{
    
   /**
    * 显示选择仓库
    * @param int $branch_id 仓库ID
    * @param string $url    form提交的地址
    * @param string $title  标题显示
    * @param string $method form提交的方式 
    */
   public function getBranchView($branch_id, $url, $title='查看', $method='GET'){
       return kernel::single("ome_branch_view")->getBranchView($branch_id, $url, $title, $method);
   }
}