<?php

class ome_mdl_return_process extends dbeav_model{
   
    /**
     * 扩展搜索项
     */
    function searchOptions(){
        
        return parent::searchOptions();
    }
    

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if(isset($filter['title'])){
            $where = " AND title like '%".addslashes($filter['title'])."%' ";
            unset($filter['title']);
        }
        if(isset($filter['order_bn'])){
            $oOrders = $this->app->model('orders');
            $oItems = $this->app->model('return_process_items');
            $order_id = $oOrders->dump(array('order_bn'=>$filter['order_bn']),'order_id');
            $return_ids_arr = $oItems->getList('return_id',array('order_id'=>$order_id));
            foreach ($return_ids_arr as $row) {
                $return_ids[] = $row['return_id'];
            }
            $where = " AND return_id IN ('".implode("','", $return_ids)." ')";
            unset($filter['order_bn']);
        }
        if(isset($filter['bn'])){
            $oItems = $this->app->model('return_process_items');
            $return_id = $oItems->getList('return_id',array('bn|has'=>$filter['bn']));
            foreach($return_id as $v){
                $vv[] = $v['return_id'];
            }
            $where = " AND return_id IN (".implode(',',$vv).") ";
            unset($filter['bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
    
    /**
 *  售后服务已处理商品详情
 *  @param int $por_id
 */
    function product_detail($reship_id,$order_id=''){
        $product_detail = $this->app->model('reship')->dump($reship_id);

        $oBranch = &$this->app->model('branch');
        $product_detail['branch_name'] = $oBranch->Get_name($product_detail['branch_id']);
        $oProduct_items = &$this->app->model('return_process_items');
        $oProblem = &$this->app->model('return_product_problem');
        $process_data = $product_detail['process_data'];
        /*仓库信息*/
      
        $product_detail['process_data'] = $process_data!='' ? unserialize($process_data):'';
        
        $filter = array('reship_id'=>$reship_id);
        if($order_id!=''){
           $filter['order_id'] = $order_id;
        }
        $items = $oProduct_items->getList('*',$filter);

        foreach($items as $k=>$v){
            $items[$k]['branch_name']=$oBranch->Getlist_name($v['branch_id']);
        }
 
        $product_detail['items'] = $items;
        $objCat = &app::get('ome')->model('return_product_problem');
        $catList =$objCat->getCatList(0);
        $product_detail['problem_type']= $catList;
        $product_detail['StockType']=$oProblem->store_type();


        /*日志列表*/
        $oOperation_log = &$this->app->model('operation_log');
        $oOperation = &$this->app->model('operations');
        if($product_detail['return_id']){
            $product_detail['log']=$oOperation_log->read_log(array('obj_type'=>'return_product@ome'),0,20,'log_id');
            $Oreturn_product = $this->app->model('return_product')->dump($product_detail['return_id']);
            $product_detail = array_merge($product_detail,$Oreturn_product);
        }else{
            $product_detail['log']=$oOperation_log->read_log(array('obj_type'=>'reship@ome','obj_id'=>$reship_id),0,20,'log_id');
        }
        
        return $product_detail;
    }
    

    function getList($cols='*',$filter=array(),$offset=0,$limit=-1,$orderType=null){
          if(empty($orderType))$orderType = "last_modified DESC";
            return parent::getList($cols,$filter,$offset,$limit,$orderType);
    }

     /*
     * 当收货完成后改变主表状态
     * @status recieved verify
     */
    function changestatus($por_id,$return_id,$type,$status){
        $oProduct = &$this->app->model('return_product');
        if($type=='recieved'){
            $sqlstr = ' AND recieved=\'true\'';
        }else if($type=='verify'){
            $sqlstr = ' AND verify=\'true\'';
        }
        $sql='select count(*) AS count from  sdb_ome_return_process where return_id='.$return_id.$sqlstr;
        $pro=$this->db->selectrow($sql);
        $sql='select count(*) AS count from  sdb_ome_return_process where  return_id='.$return_id;
        $pro_need=$this->db->selectrow($sql);
        if($pro['count']==$pro_need['count']){
            $data = array(
                'return_id'=>$return_id,
                'status'=>$status,
                'verify'=>'true',
            );
            $oProduct->save($data);
        }
    }
    /*
     * 校验售后商品
     * @status recieved verify
     */
    function changeverify($por_id,$reship_id,$return_id='',$memo=''){
        $oOperation_log = &$this->app->model('operation_log');
        $Oreship = &$this->app->model('reship');        
        $item=$this->db->selectrow('select count(*) as count from sdb_ome_return_process_items  where is_check=\'true\' and  por_id='.$por_id);
        $count=$item['count'];
        $sqlstr='select count(*) as count from sdb_ome_return_process_items where por_id='.$por_id;
        $pro_item = $this->db->selectrow($sqlstr);
        $countstr=$pro_item['count'];
		
        if($count==$countstr){
            $sdf_pro = array(
                'por_id'=>$por_id,
                'verify'=>'true'
            );
            $this->save($sdf_pro);//修改从表状态

            $sdf_reship = array(
                'reship_id'=>$reship_id,
                'is_check'=>'8'
            );
            $Oreship->save($sdf_reship);
            $memo = '质检完成:'.$memo;            
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
        }else{#售后单加质检中状态
			$sdf_reship = array(
                'reship_id'=>$reship_id,
                'is_check'=>'13'
            );
			
            $Oreship->save($sdf_reship);
			
		}

        if($memo!=''){
            $this->update(array('memo'=>$memo),array('por_id'=>$por_id));
        }

        if($return_id != ''){
           $this->changestatus($reship_id,$return_id,'verify','7');
        }
       
    }

    /**
     * 保存收货信息
     *
     * @return void
     * @author 
     **/
    function save_return_process($data){
        $Oprocess_items = $this->app->model('return_process_items');
        $Oreship_items = $this->app->model('reship_items');
        $Oreship = $this->app->model('reship');
        $reship_items = $Oreship_items->getList('*',array('reship_id' => $data['reship_id'],'return_type'=>'return'));

        $reship_info = $Oreship->dump(array('reship_id'=>$data['reship_id']));
        $ret=array();
        foreach($reship_items as $k=>$v){
            if(!isset($ret[$v['branch_id']])){
                $ret[$v['branch_id']] = $v;
            }
        }
        $code_pro_array = array();
        foreach($ret as $k => $v){
            $sdf_data = array(
                'reship_id'=>$data['reship_id'],
                'order_id'=>$reship_info['order_id'],
                'return_id'=>$reship_info['return_id'],
                'member_id'=>$reship_info['member_id'],
                'title'=>$data['title'],
                'content'=>$data['content'],
                'add_time'=>$reship_info['t_begin'],
                'shop_id'=>$reship_info['shop_id'],
                'last_modified'=>$data['last_modified'],
                'memo'=>$data['memo'],
                'branch_id'=>$k,
                'attachment'=>$data['attachment'],   
                'comment'=>$data['comment'],
                'process_data'=>$data['process_data'],
                'recieved'=>'true',
                'verify'=>'false', 
                'por_id'=>'',              
            );
        
            if($reship_info['return_id']){
                $Oreturn_product = $this->app->model('return_product');
                $product_info = $Oreturn_product->dump(array('return_id'=>$reship_info['return_id']),'title,content,add_time,shop_id,memo,attachment,comment');
                $sdf_data['title'] = $product_info['title'];
                $sdf_data['content'] = $product_info['content'];
                $sdf_data['add_time'] = $product_info['add_time'];
                $sdf_data['shop_id'] = $product_info['shop_id'];
                $sdf_data['memo'] = $product_info['memo'];
                $sdf_data['attachment'] = $product_info['attachment'];
                $sdf_data['comment'] = $product_info['comment'];
            }
            $this->save($sdf_data);
            $code_pro_array[$k] = $sdf_data['por_id'];//构造仓库为键，最后插入id为值的一维数组
        }
        


        $process_items = array();
        foreach ($reship_items as $k => $v) {   
            for($i=0;$i<$v['num'];$i++){
                $process_items['reship_id'] = $v['reship_id'];
                $process_items['order_id'] = $sdf_data['order_id'];
                $process_items['return_id'] = $sdf_data['return_id'];
                $process_items['product_id'] = $v['product_id'];
                $process_items['bn'] = $v['bn'];
                $process_items['name'] = $v['product_name'];
                $process_items['branch_id'] = $v['branch_id'];  
                $process_items['op_id'] = $v['op_id']; 
                $process_items['acttime'] = time();  
                $process_items['por_id'] = $code_pro_array[$v['branch_id']];                                             
                $rs = $this->db->exec('select * from sdb_ome_return_process_items where 0=1');
                $sql = kernel::single("base_db_tools")->getinsertsql($rs,$process_items);
                $this->db->exec($sql);
            }
        }

        return true;
    }

    /**
     * 拒绝发货后，将reship_id对应的return_process,return_process_items表中的删除
     *
     * @return void
     * @author 
     **/
    function cancel_process($refuse_memo)
    {
       $Oreship = $this->app->model('reship');
       $Oreship->update(array('reason'=>$refuse_memo['reason']),array('reship_id'=>$refuse_memo['reship_id']));
       $this->delete(array('reship_id'=>$refuse_memo['reship_id']));
       $Oprocess_items = &$this->app->model('return_process_items');
       $Oprocess_items->delete(array('reship_id'=>$refuse_memo['reship_id']));
       return true;
    }
}
?>