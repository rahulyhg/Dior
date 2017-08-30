<?php
/**
* 店铺与仓库的相关业务处理
* @access public
* @author dqiujing@gmail.com
* @copyright shopex.cn 2012.3.13
*/
class ome_shop_branch{
    
    /**
    * 设置店铺与仓库的关联关系
    * @access public
    * @param String $branch_bn 仓库BN
    * @param array $shop_bns 当前仓库所关联的所有店铺bn
    * @return bool
    */
    static function update_relation($branch_bn,$shop_bns,$branch_id='',$append=false){
        if (empty($branch_bn)) return false;
        $relation = app::get('ome')->getConf('shop.branch.relationship');

        foreach ((array)$shop_bns as $shop_bn) {
            $new[$shop_bn][$branch_id] = $branch_bn;
        }
        
        $allBranch = $offlineBranch = array();
        $rows = app::get('ome')->model('branch')->getAllBranchs('branch_id,branch_bn,attr');
        foreach ($rows as $key=>$row) {
            $allBranch[$row['branch_id']] = $row['branch_bn'];
            if ($row['attr'] == 'false') {
                $offlineBranch[$row['branch_id']] = $row['branch_bn'];
            }
        }
        
        $allShop = array();
        $rows = app::get('ome')->model('shop')->getList('shop_id,shop_bn');
        foreach ($rows as $key=>$row) {
            $allShop[$row['shop_bn']] = $row['shop_id'];
        }

        if ($relation) {
            foreach ($relation as $shop_bn=>&$branchs1) {
                if($append===false) unset($branchs1[$branch_id]);

                # 过滤掉不存在的仓库
                $branchs1 = array_intersect_key((array)$branchs1,(array)$allBranch);
                
                # 过滤掉线下仓库
                $branchs1 = array_diff_key((array)$branchs1,(array)$offlineBranch);

                # 过滤掉重复仓库编号
                $branchs1 =  array_unique($branchs1);
                
                if ( empty($branchs1) ) unset($relation[$shop_bn]); 

                # 过滤掉不存在店铺
                if (!isset($allShop[$shop_bn])) {
                    unset($relation[$shop_bn]);
                }
            }
                
            if ($new) {
                foreach ($new as $shop_bn=>$branchs2) {
                    $relation[$shop_bn] = (array)$branchs2+(array)$relation[$shop_bn];
                }
            }

        } else {
            $relation = $new;
        }
       
        app::get('ome')->setConf('shop.branch.relationship', $relation);
        return true;
    }

}