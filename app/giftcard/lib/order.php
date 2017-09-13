<?php
class giftcard_order{	
	
	public function getOrderBn($order){
		$objGoods=app::get("ome")->model('goods');
		
		$card_id=$order['card_list'][0]['card_id'];
		$code=$order['card_list'][0]['code'];
		$arrGoods=$objGoods->db->select("SELECT p.bn FROM sdb_ome_products p LEFT JOIN sdb_ome_goods g ON g.goods_id=p.goods_id WHERE g.card_id='".$card_id."'");
		$bn=$arrGoods[0]['bn'];
		if(empty($bn))return false;
		
		$order_bn=$bn.$code;
		
		return $order_bn;
	}
	
	public function filterNickName($str){
		if($str){
            $name = $str;
            $name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
            $name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
            $return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));
			
            if(!$return){
                return 'WX_'.time();
            }
        }else{
            $return = 'WX_'.time();
        }    
        return $return;
	}
	
}