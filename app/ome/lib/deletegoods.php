<?php
/**
* 店铺接口类
* @author dqiujing@gmail.com
* @copyright shopex.cn
* @date 2012.12.7
*/
class ome_deletegoods{

   
    function import(){
        $file_name = PUBLIC_DIR.'/dior.csv';
	    $file = fopen($file_name,'r');
	    $line = 0;
	    while ($data = fgetcsv($file)) {
			$line++;
	
			$goods[] = $data;
		}
		fclose($file);
		$obj =app::get('ome')->model('goods');
		$remainGoods = array();
		foreach($goods as $key => $row){
			if($key==0){
				continue;
			}
			$info = $obj->getList('*',array('bn'=>$row[1]));
			if($info){
				continue;
			}else{
				$remainGoods[] = $row;
			}

		}
		$file_name = PUBLIC_DIR.'/new_goods.csv';
		
		//echo "<pre>";print_r($remainGoods);exit;
		$new_file = fopen($file_name,"w");
		foreach($remainGoods as $val){
			fputcsv($new_file,$val);
		}
		fclose($new_file);
		//echo "<pre>";print_r($region);exit;


    }

	public function update_name(){
		$file_name = PUBLIC_DIR.'/update_name.csv';
	    $file = fopen($file_name,'r');
	    $line = 0;
	    while ($data = fgetcsv($file)) {
			$line++;
	
			$goods[] = $data;
		}

		fclose($file);
		$goodsObj = app::get('ome')->model('goods');
		$productObj = app::get('ome')->model('products');
		foreach($goods as $key=>$value){
			$product_info = $productObj->getList('product_id,goods_id',array('bn'=>$value[0]));
			$product_id = $product_info[0]['product_id'];
			$goods_id = $product_info[0]['goods_id'];
			if(!$product_id){
				continue;
			}
			
			$goodsObj->update(array('name'=>$value[1]),array('goods_id'=>$goods_id));

			$productObj->update(array('name'=>$value[1]),array('product_id'=>$product_id));

		}
	}

}