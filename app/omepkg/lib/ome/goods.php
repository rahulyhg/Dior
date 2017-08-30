<?php
class omepkg_ome_goods{
    public function after_save($goods){

        if($goods){
            foreach($goods['product'] as $k=>$v){
                $product_id = $v['product_id'];
                $goods_id = $v['goods_id'];
                $sql = "SELECT * FROM sdb_omepkg_pkg_product WHERE product_id=$product_id";

                $gondsInfo = kernel::database()->select($sql);
                if ($gondsInfo){
                    $sql1 = "UPDATE sdb_omepkg_pkg_product SET name='".$v['name']."' WHERE product_id=$product_id";

                    kernel::database()->select($sql1);
                }
            }
        }




    }




}
