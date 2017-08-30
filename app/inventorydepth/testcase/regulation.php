<?php
class regulation extends PHPUnit_Framework_TestCase
{
    function setUp() {
    
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function fgetlist_csv(&$data) 
    {
        $result = kernel::single('inventorydepth_mdl_shop_frame')->fgetlist_csv($data,array('shop_id'=>'680278fc7422e669669276c47310bb4b'),0);
        if ($result === true) {
            $this->fgetlist_csv($data);
        }
        return $data;
    }

    public function testRun(){

        //$this->fgetlist_csv($data);

//$a = kernel::single('inventorydepth_stock_products')->init();return;

        //kernel::single('inventorydepth_logic_stock')->start();
        //kernel::single('inventorydepth_logic_frame')->start();
    
    //error_log(var_export($data,true),3,DATA_DIR.'/log.log');
        //exit;
        /*
$approve_status[0] = array(
'iid' => 'F13D02560000000004010000210B1401',
'approve_status' => 'instock',
'bn' => '',
);
$stock[0] = array(
    'bn' => 'a11',    
    'quantity' => '67',
    'memo' => array(),
);*/
        //$result = kernel::single('inventorydepth_taog_rpc_request_stock')->items_quantity_list_update($stock,'680278fc7422e669669276c47310bb4b');
        $result = $this->items_all_get(array('approve_status'=>'onsale'),'680278fc7422e669669276c47310bb4b',100,50);
echo "<pre>"; print_r($result);exit;

        $result = kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_list_update($approve_status,'680278fc7422e669669276c47310bb4b');
        echo "<pre>"; print_r($result);exit;
        //error_log(var_export(json_decode($result->data),true),3,DATA_DIR.'/log.log');
        //kernel::single('inventorydepth_taog_rpc_request_stock')->items_quantity_list_update(array ( array ( 'bn'=> 'ertertert-15972039315' , 'quantity' => 39 , 'memo' => '' ) ) ,'0c922f9b185d4086379c489a0afd7435');
        //$return = $this->items_all_get(array('approve_status'=>'instock'));error_log(var_export($return,true),3,DATA_DIR.'/log.log');
        //base_kvstore::instance('inventorydepth/apply/23423424')->fetch('apply-lastexectime',$lastExecTime);
        //    var_dump($lastExecTime);
    }

    public function items_all_get($filter=array(),$shop_id='0c922f9b185d4086379c489a0afd7435',$offset=1,$limit=200)
    {
        $timeout = 20;

        if(!$shop_id) return false;

        $param = array(
                'page_no'        => $offset,
                'page_size'      => $limit,
                'fields'         => 'iid,outer_id,bn,num,title,default_img_url,modified,detail_url,approve_status,skus,price,barcode ',
            );
        
        $param = array_merge((array)$param,(array)$filter);
        
        $api_name = 'store.items.all.get';

        return kernel::single('ome_rpc_request')->call($api_name,$param,$shop_id,$timeout); 
    }
}