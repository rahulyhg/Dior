<?php
class omepkg_ome_product{
    public function checkProductByBn($bn){
        $sql = "SELECT goods_id FROM `sdb_omepkg_pkg_goods` WHERE `pkg_bn`='".trim($bn)."' ";
        $gondsInfo = kernel::database()->selectrow($sql);

        if(empty($gondsInfo)){
            return false;
        }
        return $gondsInfo['goods_id'];
    }
    
    public function getProductByBn($bn){
        $sql = "SELECT goods_id as product_id,pkg_bn as bn,name FROM `sdb_omepkg_pkg_goods` WHERE `pkg_bn`='".trim($bn)."' ";
        $gondsInfo = kernel::database()->selectrow($sql);

        if(empty($gondsInfo)){
            return false;
        }
        return $gondsInfo;
    }
    
    public function getProductInfoByBn($bn){
        $goodsObj   = &app::get('omepkg')->model('pkg_goods');
        $productObj = &app::get('omepkg')->model('pkg_product');
        
        $goodsInfo = $goodsObj->dump(array('pkg_bn'=>$bn),"goods_id,pkg_bn as bn,name,weight");
        if (!$goodsInfo) return false;
        
        $products = $productObj->getList('*',array('goods_id'=>$goodsInfo['goods_id']),0,-1);
        if ($products){
            $items = array();
            foreach ($products as $val){
                $val['nums'] = $val['pkgnum'];
                $items[] = $val;
            }
            $goodsInfo['items'] = $items;
        }
        $goodsInfo['product_id']    = $goodsInfo['goods_id'];
        $goodsInfo['product_type']  = 'pkg';
        $goodsInfo['product_desc']  = '捆绑商品';
        
        return $goodsInfo;
    }
}
