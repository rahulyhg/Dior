<?php
class omeftp_auto_update_product{
	

	public function __construct(){
		$this->app = $app;
        $this->file_obj = kernel::single('omeftp_type_txt');
		$this->ftp_operate = kernel::single('omeftp_ftp_operate');
		$this->operate_log = kernel::single('omeftp_log');

		$this->product_mdl = app::get('ome')->model('products');
		$this->branch_product_mdl = app::get('ome')->model('branch_product');
		$this->goods_mdl = app::get('ome')->model('goods');
	}
	
	//读取AX库存   同步到Magento
	public function update_store(){
		$file_arr = $this->getFtpFile('Stock_10_CN_ECO');
		//echo "<pre>";print_r($file_arr);exit;
		foreach($file_arr as $filename){
			$info = $this->file_obj->toRead(array('file'=>$filename),$msg);
//echo "<pre>";print_r($info);exit;
			$product_arr = explode("\n",$info);
			foreach($product_arr as $row){	
				$pinfo = array();
				$pinfo = explode('|',$row);
				if($pinfo[0]=='HEADER'){
					continue;
				}

				$product_id = $this->product_mdl->getList('product_id',array('bn'=>$pinfo[11]));
				//echo "<pre>";print_r($product_id);exit;
				if(empty($product_id[0])){
					continue;
				}
				$store_freeze = $this->branch_product_mdl->getList('store_freeze',array('product_id'=>$product_id[0]['product_id'],'branch_id'=>1));
				$store = $pinfo[14]+$store_freeze[0]['store_freeze'];
				$re = $this->branch_product_mdl->change_store(1,$product_id[0]['product_id'],$store);
				if($re){
					kernel::single('omemagento_service_product')->update_store($pinfo[11],$pinfo[14]);
				}
			}
		}
	}

	//读取AX库存   同步到Magento
	public function update_store_all(){
		$file_arr = $this->getFtpFile('Stock_10_CN_ECO');
		//echo "<pre>";print_r($file_arr);exit;
		$all_product = $this->product_mdl->getList('product_id,bn');
		$all_product = $this->product_mdl->db->select("select p.product_id,p.bn from sdb_ome_products as p left join sdb_ome_goods as g on g.goods_id=p.goods_id where g.is_prepare='false'");
		if(!$file_arr){
			$error_nums = app::get('ome')->getConf('ftp_store_error');
			$error_nums++;
			app::get('ome')->setConf('ftp_store_error',$error_nums);
			if($error_nums>25){
				$msg = 'Dior已经在1个小时未获取到库存文件，请查看！';
				$has_send = app::get('ome')->getConf('ftp_store_error_send');
				if(!$has_send){
					kernel::single("omeftp_auto_update_email_sendemail")->sendEmail($msg);
					app::get('ome')->setConf('ftp_store_error_send',true);
				}
			}
		}else{
			app::get('ome')->setConf('ftp_store_error',0);
			app::get('ome')->setConf('ftp_store_error_send',false);
		}
		foreach($file_arr as $filename){
			if(strpos($filename,'bal')){
				continue;
			}
			$info = $this->file_obj->toRead(array('file'=>$filename),$msg);

			$charset[1] = substr($info, 0, 1);
			$charset[2] = substr($info, 1, 1);
			$charset[3] = substr($info, 2, 1);
			if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
				$info = substr($info, 3);
			}
			if(!$info){
				continue;
			}
			$product_arr = explode("\n",$info);

			$file_all_products = array();
			$file_all_bn = array();
			foreach($product_arr as $row){	
				$pinfo = array();
				$pinfo = explode('|',$row);
				if($pinfo[0]=='HEADER'){
					continue;
				}

				$product_id = $this->product_mdl->getList('product_id',array('bn'=>$pinfo[11]));
				//echo "<pre>";print_r($product_id);exit;
				if(empty($product_id[0])){
					continue;
				}
				$file_all_products[$pinfo[11]] = $pinfo;
				
			}
			foreach($all_product as $product){
				$bn = $product['bn'];
				if($file_all_products[$bn]){
					$file_pinfo = $file_all_products[$bn];
					$store_freeze = $this->branch_product_mdl->getList('store_freeze',array('product_id'=>$product['product_id'],'branch_id'=>1));
					$store = $file_pinfo[14]+$store_freeze[0]['store_freeze'];
					$re = $this->branch_product_mdl->change_store(1,$product['product_id'],$store);
					if($re){

						$hasUser = $this->getHasUseStore($product['bn']);
						$magentoStore = $file_pinfo[14]-$hasUser;
						if($magentoStore<0){
							$magentoStore = 0;
						}
						kernel::single('omemagento_service_product')->update_store($product['bn'],$magentoStore);
					}

				}else{
				//	error_log(var_export($product['bn']."\n",true),3,__FILE__.'cc.txt');

					$store_freeze = $this->branch_product_mdl->getList('store_freeze',array('product_id'=>$product['product_id'],'branch_id'=>1));
					$store = $store_freeze[0]['store_freeze'];
					$re = $this->branch_product_mdl->change_store(1,$product['product_id'],$store);
					if($re){
						kernel::single('omemagento_service_product')->update_store($product['bn'],0);
					}
				}
			}
		}
	}


	public function update_price(){
		$file_arr = $this->getFtpFile('Tariff_10_CN_ZTT');
		//echo "<pre>";print_r($file_arr);exit;
		$objUpdatePrice = app::get('omemagento')->model('update_price');

		if(!$file_arr){
			$msg = 'Dior未读取到价格文件，请查看！';
			kernel::single("omeftp_auto_update_email_sendemail")->sendEmail($msg);
			app::get('ome')->setConf('ftp_price_error','true');
		}else{
			app::get('ome')->setConf('ftp_price_error','false');
		}
		foreach($file_arr as $filename){
			if(strpos($filename,'bal')){
				continue;
			}
			$info = kernel::single('omeftp_type_txt')->toRead(array('file'=>$filename),$msg);
			$charset[1] = substr($info, 0, 1);
			$charset[2] = substr($info, 1, 1);
			$charset[3] = substr($info, 2, 1);
			if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
				$info = substr($info, 3);
			}
			if(!$info){
				continue;
			}
			$product_arr = explode("\n",$info);
			foreach($product_arr as $row){
				$pinfo = array();
				$pinfo = explode('|',$row);
				if($pinfo[0]=='HEADER'){
					continue;
				}
				$product_id = $this->product_mdl->getList('product_id,goods_id',array('bn'=>$pinfo[5]));
				if(empty($product_id[0])){
					continue;
				}

				$upDate = array();
				$upDate = array(
						'product_bn'=>$pinfo[5],
						'start_time'=>strtotime($pinfo[14]),
						'end_time'=>strtotime($pinfo[15]),
						'price'=>$pinfo[19],
						'discount_precent1'=>$pinfo[20],
						'discount_precent2'=>$pinfo[21],
						'discount_amount'=>$pinfo[22],
						'createtime'=>time(),
					);
				if($upDate['start_time']<time()&&$upDate['end_time']>time()){
					$this->product_mdl->update(array('price'=>$upDate['price']),array('product_id'=>$product_id[0]['product_id']));
					$this->goods_mdl->update(array('price'=>$upDate['price']),array('goods_id'=>$product_id[0]['goods_id']));
					kernel::single('omemagento_service_product')->update_price($upDate['product_bn'],$upDate['price']);
				}else{
					if($upDate['start_time']>time()){
						$objUpdatePrice->insert($upDate);
					}
				}
				
				/*$data = array();
				$data['price'] = $pinfo[18];
				$data['product_id'] = $product_id[0]['product_id'];
				$this->product_mdl->save($data);*/
			}
		}
	}

	public function getFtpFile($file_prefix='_Stock_10_CN_ECO',$dir='/FROM_AX'){
		$list = $this->ftp_operate->get_file_list($dir);
		//echo "<pre>";print_r($list);exit;
		$str = $file_prefix;
		$file_arr = array();
		$file_list = array();
		foreach($list as $key=>$value){
			if(strpos($value,'bal')){
				$file_list[] = $value;
				$file_list[] = str_replace('bal','dat',$value);
			}
		}
		//echo $str;
		foreach((array)$file_list as $filename){
			$params = array();
			//var_dump(strpos($filename,$str));
			if(strpos($filename,$str)){
			
				$params['remote'] = '/FROM_AX/'.$filename;
				if(!file_exists(ROOT_DIR.'/ftp/Testing/out/')){
					mkdir(ROOT_DIR.'/ftp/Testing/out/',0777,true);
				}
				$local = ROOT_DIR.'/ftp/Testing/out/'.$filename;
				$params['local'] = $local;
				$params['resume'] = 0;
				$sign = $this->ftp_operate->pull($params,$msg);
				//echo "<pre>";var_dump($sign);exit;
				if($sign){
					$file_arr[] = $local;
					$this->ftp_operate->delete_ftp($params['remote']);
				}
			}

		}
		return $file_arr;
	}


	public function getHasUseStore($bn){
		
		$sql = "SELECT sum(sdb_ome_order_items.nums) as nums from sdb_ome_order_items LEFT JOIN sdb_ome_orders  ON sdb_ome_orders.order_id=sdb_ome_order_items.order_id WHERE bn='".$bn."' and process_status IN ('confirmed','unconfirmed')";

		$nums = app::get('ome')->model('orders')->db->select($sql);

		return $nums[0]['nums'];

	}

}