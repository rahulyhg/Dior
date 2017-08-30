<?php
class ome_goods_product{
    public function checkProductByBn($bn){
        $productObj = &app::get('ome')->model('products');
        $productInfo = $productObj->dump(array('bn'=>$bn),"product_id");

        if(!$productInfo){
            return false;
        }
        return $productInfo['product_id'];
    }
    
    public function getProductByBn($bn){
        $productObj = &app::get('ome')->model('products');
        $productInfo = $productObj->dump(array('bn'=>$bn),"product_id,bn,name");

        if(!$productInfo){
            return false;
        }
        return $productInfo;
    }
    #验证正数(包括小数)
    function valiPositive($data = null){
        if(is_numeric( $data)){
            $new = explode('.',$data);
            $count = count($new);
            if(1 == $count){
                $patter = '/^[1-9]{1}[0-9]{0,}$/';
                preg_match($patter,$new[0],$arr);
                if(empty($arr)){
                    return false;
                }
            }elseif(2 == $count){
                $patter = '/^(?:(?:[0-9]{1})||(?:[1-9]{1}[0-9]{1,}))$/';
                preg_match($patter,$new[0],$arr);
                if(empty($arr)){
                    return false;
                }
            }
            if($data<=0){
                return false;
            }
            return true;
        }else{
            return false;
        }
    }
}
