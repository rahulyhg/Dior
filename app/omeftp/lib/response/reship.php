<?php
class omeftp_response_reship{
	
	 public function __construct(&$app)
    {
        $this->app = $app;

        $this->file_obj = kernel::single('omeftp_type_txt');
		$this->ftp_operate = kernel::single('omeftp_ftp_operate');

		$this->operate_log = kernel::single('omeftp_log');
    }

	public function getFtpFile($file_prefix='RDER_RET_DIOR_10_',$dir='/FROM_AX'){
		$list = $this->ftp_operate->get_file_list($dir);
		$str = $file_prefix;
		$file_arr = array();

		$file_list = array();
		foreach($list as $key=>$value){
			if(strpos($value,'bal')){
				$file_list[] = $value;
				$file_list[] = str_replace('bal','dat',$value);
			}
		}
		$ftpLogObj = app::get('omeftp')->model('ftplog');
		//echo $str;
		foreach($file_list as $filename){
			$params = array();
			//var_dump(strpos($filename,$str));
			if(strpos($filename,$str)){

				$params['remote'] = '/FROM_AX/'.$filename;
				if(!file_exists(ROOT_DIR.'/ftp/Testing/out/')){
					mkdir(ROOT_DIR.'/ftp/Testing/out/',0777,true);
				}
				if(!file_exists(ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()))){
					mkdir(ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()),0777,true);
				}
				$local = ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()).'/'.$filename;
			
				$ftp_log_data = array();
				$ftp_log_data = array(
							'io_type'=>'in',
							'work_type'=>'reship',
							'createtime'=>time(),
							'file_local_route'=>$local,
							'file_ftp_route'=>$filename,
						);

				$params['local'] = $local;
				$params['resume'] = 0;
				$sign = $this->ftp_operate->pull($params,$msg);
				//echo "<pre>";var_dump($sign);exit;
				if($sign){
					$file_arr[] = $local;
					$this->ftp_operate->delete_ftp($params['remote']);
					$ftp_log_data['status']='succ';
				}else{
					$ftp_log_data['status']='fail';
				}

				$ftpLogObj->insert($ftp_log_data);
			}

		}
		return $file_arr;
	}


	public function down_load(){
		$list = $this->getFtpFile('RDER_RET_DIOR_10_');
		//echo "<pre>";print_r($list);exit;
		foreach($list as $filename){
			if(strpos($filename,'bal')){
				continue;
			}
			$this->read_order($filename);
		}
	}
	
	public function read_order($file_name){
		$params['file'] = $file_name;
		$info = $this->file_obj->toRead($params,$msg);
		$orders = array();
		$index = -1;
		$arr = explode("\n",$info);
		foreach($arr as $v){
			$line = array();
			$charset[1] = substr($v, 0, 1);
			$charset[2] = substr($v, 1, 1);
			$charset[3] = substr($v, 2, 1);
			if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
				$v = substr($v, 3);
			}
			$line = explode('|',$v);
			if($line[0]=='HEADER'){
				continue;
			}
			if($line[0]=='H'){
				$index++;
			}
			$orders[$index][$line[0]][] = $line;
		}
		foreach($orders as $order){
			$this->do_finish($order);
		}
	}

	public function do_finish($data){	//echo "<pre>";print_r($data);exit;
		$_POST = array();
		$mdl_order = app::get('ome')->model('orders');
		$mdl_reship = app::get('ome')->model('reship');
		$reship  = app::get('ome')->model('reship_items');

		$oProduct_pro = app::get('ome')->model('return_process');
        $oProduct = app::get('ome')->model('return_product');
        $oProblem_type = app::get('ome')->model('return_product_problem_type');
        $oProblem = app::get('ome')->model('return_product_problem');
        $oBranch = app::get('ome')->model('branch');
        $productSerialObj = app::get('ome')->model('product_serial');
        $serialLogObj = app::get('ome')->model('product_serial_log');
        $oOperation_log = app::get('ome')->model('operation_log');//写日志
        $pro_items = app::get('ome')->model('return_process_items');

		$productObj = app::get('ome')->model('products');
        $goodsObj = app::get('ome')->model('goods');
        $productSerialObj = app::get('ome')->model('product_serial');
		
		$order_bn = $data['H'][0][5];
		$order_bn_arr = explode('-',$order_bn);
		$order_bn = $order_bn_arr[0];
		$reship_index = str_replace('R','',$order_bn_arr[1]);
		
		$order_info = $mdl_order->getList('order_id',array('order_bn'=>$order_bn));
		
		$reships= $mdl_reship->getList('*',array('order_id'=>$order_info[0]['order_id']));
		$reships = array_reverse($reships);
		$reshipInfo = array();
		foreach($reships as $k=>$info){
			if($reship_index==($k+1)){
				$reshipInfo = $info;
			}
		}

		$reship_id = $reshipInfo['reship_id'];
		$row = $mdl_reship->getList('reship_id,is_check',array('reship_id'=>$reship_id,'is_check'=>array('1','3','13')));
		if (!$row) {
			error_log(var_export($order_bn,true),3,__FILE__.'error.txt');//记录无法更新的退货单
        }
		if($row[0]['is_check']=='1'){
			kernel::single('ome_return_rchange')->accept_returned($reship_id,'3',$error_msg);
		}
	
		$oProduct_pro_detail = $oProduct_pro->product_detail($reship_id,$order_info[0]['order_id']);
		foreach($oProduct_pro_detail['items'] as $key => $val){
			if($val['return_type'] == 'change'){
                unset($oProduct_pro_detail['items'][$key]);
                break;
            }

			if (!isset($bnArr[$val['bn']])) {
                 $bnArr[$val['bn']] = $productObj->dump(array('bn'=>$val['bn']), 'goods_id,barcode,spec_info');;
			}
            //$p = $productObj->dump(array('bn'=>$val['bn']), 'goods_id,barcode,spec_info');
			$p = $bnArr[$val['bn']];
            if (!isset($gArr[$p['goods_id']])) {
                 $gArr[$p['goods_id']] = $goodsObj->dump($p['goods_id'], 'serial_number');
			}
            //$g = $goodsObj->dump($p['goods_id'], 'serial_number');
			$g = $gArr[$p['goods_id']];

            $mixed_array['bn_'.$val['bn']] = $val['bn'];
            
            //判断条形码是否为空
            if(!empty($p['barcode'])){
               $mixed_array['barcode_'.$p['barcode']] = $val['bn'];
            }

            /* 退货数量 */
            if($product_process['items'][$val['bn']]){
                $product_process['items'][$val['bn']]['num'] += $val['num'];
            }else{
                $product_process['items'][$val['bn']] = $val;
            }

            if(!empty($serial_product['serial_number'])){
               $product_process['items'][$val['bn']]['serial_number'] = $serial_product['serial_number'];
            }

            $product_process['items'][$val['bn']]['barcode'] = $p['barcode'];

            /* 校验数量 */
            if($val['is_check'] == 'true'){
                $product_process['items'][$val['bn']]['checknum'] += $val['num'];
                $oProduct_pro_detail['items'][$key]['checknum'] = $val['num'];
            }

            $product_process['items'][$val['bn']]['itemIds'][] = $val['item_id'];

            if($val['is_check'] == 'false'){
                /* 退货数量 */
                if($forNum[$val['bn']]){
                    $forNum[$val['bn']] += 1;
                    $oProduct_pro_detail['items'][$key]['fornum'] = $forNum[$val['bn']];
                }else{
                    $oProduct_pro_detail['items'][$key]['fornum'] = 1;
                    $forNum[$val['bn']] = 1;
                }
            }
            $product_process['items'][$val['bn']]['spec_info'] = $p['spec_info'];
            unset($oProduct_pro_detail['items'][$key]);
            $product_process['por_id'] = $val['por_id'];
		}

		foreach($data['L'] as $item){
			$items[] = array(
					'product_bn'=>$item['4'],
					'num'=>$item['15'],
				);
		}
		foreach($items as $val){
			$_POST['bn_'.$val['product_bn']] = $val['product_bn'];
		}
		foreach($product_process['items'] as $key=>$val){
			foreach($val['itemIds'] as $itemId){
				$_POST['instock_branch'][$key.$itemId] = 1;
				$_POST['process_id'][$itemId] = $key;
				$_POST['memo'][$key.$itemId] = '自动质检';
				$_POST['store_type'][$key.$itemId] = 0;
				$_POST['check_num'][$key.$itemId] = 1;
			}
		}
		$_POST['check_type'] = 'bn';

		$_POST['reship_id'] = $reship_id;
		$_POST['por_id'] = $product_process['por_id'];//echo "<pre>";print_r($_POST);exit;
		$sign = kernel::single('ome_return')->toQC($reship_id,$_POST,$msg);
		if($sign){
			return true;
		}else{
			error_log(var_export($order_bn,true),3,__FILE__.'error.txt');//记录无法更新的退货单
		}
	}
	
}