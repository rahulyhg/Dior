<?php
/**
 * 赠品相关
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2013
 *
 */
class ome_rpc_response_gift extends ome_rpc_response
{
    public static $_productModel = null;

	 //获取赠品列表CRM
	 public function getlist($result){
		 $offset = $result['page']?($result['page']-1):0;
		 $limit = $result['limit']?$result['limit']:500;
//         $cols = $result['cols']?$result['cols']:'gift_bn,gift_name';
         $cols = 'gift_bn,gift_name';
         
		 $data = array();
	     $giftObj = app::get('crm')->model('gift');
         $i = 0;
         $filter = array();
		 while($offset >= $i){
		     $gifts = $giftObj->getList($cols,$filter,$offset*$limit,$limit);
			 $data = array_merge($data,$gifts);
			 $i++;
		 }
		 foreach ($data as $k => $giftInfo) {
		     $product_data = $this->getProductInfo($giftInfo['gift_bn']);
		     
		     $data[$k]['gift_num'] = $product_data['store'];
		     $data[$k]['price'] = $product_data['price'];
		     $data[$k]['cost'] = $product_data['cost'];
		 }
		 return $data;
	 }
	 
    public function getProductInfo($bn) {
        if (self::$_productModel == null) {
            self::$_productModel = app::get('ome')->model('products');
        }
        $result = self::$_productModel->getList('store,price,cost', array('bn' => $bn));
        $store = isset($result[0]['store']) ? $result[0]['store'] : 0;
        $data['store'] = $store;
        $data['price'] = $result[0]['price'];
        $data['cost'] = $result[0]['cost'];
        return $data;
    }

}