<?php
class console_mdl_adjustNumber extends console_mdl_interface_iostocksearchs{
    public $kvdata = array();

    //定义导入文件模版字段
    public function import_title(){
        $title[1] = array(
            '*:货号',
            '*:货品名称',
            '*:仓库编号',
            '*:调整到的数量',
            '*:备注',
        );

        return $title;
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    function io_title($filter){     
        switch( $filter ){
            case 'adjust':
            default:
                $this->oSchema['csv'][$filter] = array(
                    '*:货号' =>'',
                    '*:货品名称' =>'',
                    '*:仓库编号' =>'',
                    '*:调整到的数量' =>'',
                    '*:备注' =>'',
                );
            break;
        }
        return  $this->ioTitle['csv'][$filter] = array_keys( $this->oSchema['csv'][$filter] );   
    }



    
     function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $this->aa++;
        $mark = false;
        $fileData = $this->kvdata;
        
        if( !$fileData ) $fileData = array();

        if( substr($row[0],0,2) == '*:' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{

            $bn = trim($row[0]);
            $branch_bn = $row[2];
            $nums = $row[3];
            $memo = $row[4];
            if ($bn && $branch_bn && ($nums>=0)) {
                //判断货号是否存在
                $proObj = app::get('ome')->model('products');
                $oBranch_product = app::get('ome')->model('branch_product');
                $products = $proObj->dump(array('bn'=>$bn),'product_id,type');
                if(!$products){
                    $msg['error'] = '货号不存在 '.$bn;
                    return false;
                }else{
                    if($products['type'] != 'normal'){
                        $msg['error'] ='货号 '.$bn.'不是基础货品 ';
                        return false;
                    }
                }

                //判断仓库编号是否存在
                $branchObj = &app::get('ome')->model('branch');
                $branch = $branchObj->dump(array('branch_bn'=>$branch_bn),'branch_id,branch_bn');
                if( ! $branch){
                    $msg['error']= '编号 '.$branch_bn.' 仓库不存在 ';
                    return false;
                }

                //判断数量是否合法
                if($nums < 0){
                    $msg['error'] = '货号 '.$bn.' 数量应为正整数 ';
                    return false;
                }
                
                $branch_product = $oBranch_product->dump(array('branch_id'=>$branch['branch_id'],'product_id'=>$products['product_id']),'store,store_freeze');
                
                if($branch_product['store_freeze'] > $nums){
                    $msg['error'] = '货号 '.$bn.' 冻结大于调整数量 ';
                    return false;
                }
                 if(empty($branch_product)){
                    $branch_product['store'] = 0;
                    $branch_product['store_freeze'] = 0;
                    
                }
                $diff_nums = $branch_product['store']-$nums;
                if($diff_nums == 0){
                    $msg['error'] = '货号 '.$bn.' 库存数量无差异变化 ';
                    return false;
                }
               
                $type = $diff_nums < 0 ? "IN" : "OUT";
                $fileData[$type][] = array(
                    'product_id'=>$products['product_id'],
                    'bn'   =>$bn,
                    'branch_id'=>$branch['branch_id'],
                    'nums'           =>abs($diff_nums),
                    'memo'=>$memo,
                );
               

                
            }
            $this->kvdata = $fileData;
            
        }
        return null;

     }

    function prepared_import_csv(){
        set_time_limit(0);

    }

    function finish_import_csv(){
        $oQueue = &app::get('base')->model('queue');
        $data = $this->kvdata; unset($this->kvdata);
        
        $oBranch_product = app::get('ome')->model('branch_product');
        $adjustout = array();
        $adjustin = array();

        foreach ($data as $k=>$v ) {
            
            if ($k == 'IN') {
                $adjustLib = kernel::single('siso_receipt_iostock_adjustnumberin');
            }else{
                $adjustLib = kernel::single('siso_receipt_iostock_adjustnumberout');
            }
            $adjustLib->create($v, $createdata, $msg);
        }
        return null;
    }
}
