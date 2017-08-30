<?php
/**
* 货品库存管理
* @author dqiujing@gmail.com
* @copyright shopex.cn 2012.3.2
*/
class ome_stock_products{

    /**
    * 增加销售预占
    * @access public
    * @param Int $product_id 货品ID
    * @param Int $freeze_store 预占库存量
    * @param String $shop_id 店铺ID
    * @return bool
    */
    public function freeze($product_id,$freeze_store,$shop_id=''){
        if (empty($product_id) || empty($freeze_store)) return false;
        
        //增加货品表销售预占
        $productsModel = &app::get('ome')->model('products');
        $productsModel->freez($product_id,$freeze_store);

        //更新店铺销售预占
        //$this->update_shopSaleFreeze($product_id,$shop_id,$freeze_store,'+'); 暂不用

        //推送库存数据:TODO:改为crontab每分钟队列触发,不实时推送
        //kernel::single('ome_stock_service')->push($product_id,'shop');
    }

    /**
    * 减少销售预占
    * @access public
    * @param Int $product_id 货品ID
    * @param Int $freeze_store 预占库存量
    * @param String $shop_id 店铺ID
    * @return bool
    */
    public function unfreeze($product_id,$freeze_store,$shop_id=''){
        if (empty($product_id) || empty($freeze_store)) return false;
        
        //减少货品表销售预占
        $productsModel = &app::get('ome')->model('products');
        $productsModel->unfreez('',$product_id,$freeze_store);

        //更新店铺销售预占
        //$this->update_shopSaleFreeze($product_id,$shop_id,$freeze_store,'-'); 暂不用

        //推送库存数据TODO:改为crontab每分钟队列触发,不实时推送
        //kernel::single('ome_stock_service')->push($product_id,'shop');
    }

    /**
    * 更新店铺销售预占
    * @access public
    * @param Int $product_id 货品ID
    * @param String $shop_id 店铺ID
    * @param Int $freeze_store 预占库存量
    * @param String $operator 运算符:"+"代表增加,"-"代表减少
    * @return bool
    */
    public function update_shopSaleFreeze($product_id,$shop_id,$freeze_store,$operator){
        if (empty($shop_id) || empty($product_id)) return false;

        $shopSaleFreezeModel = &app::get('ome')->model('shop_sale_freeze');
        if ($shopSaleFreezeModel->count(array('product_id'=>$product_id,'shop_id'=>$shop_id))){
            $now = time();
            $update_fields = "";
            switch($operator){
                case "+":
                    $update_fields = "freeze_store=IFNULL(freeze_store,0)+".$freeze_store.",";
                    break;
                case "-":
                    $update_fields = " freeze_store=IF((CAST(freeze_store AS SIGNED)-$freeze_store)>0,freeze_store-$freeze_store,0),";
                    break;
                case "=":
                default:
                    $update_fields = "freeze_store=".$freeze_store.",";
                    break;
            }
            $sql = sprintf('UPDATE `sdb_ome_shop_sale_freeze` SET %s last_modified=\'%s\' WHERE product_id=\'%s\' AND shop_id=\'%s\'',$update_fields,$now,$product_id,$shop_id);
            return kernel::database()->exec($sql);
        }else{
            $sdf = array(
                'product_id' => $product_id,
                'shop_id' => $shop_id,
                'freeze_store' => $freeze_store
            );
            return $shopSaleFreezeModel->insert($sdf);
        }
    }

}