<?php
class ome_ctl_admin_storageexport extends desktop_controller{
    var $name = "库存导入";
    var $workground = "storage_center";

    function index(){
       //$corps

       $oBranch = &$this->app->model('branch');
       $branch=$oBranch->getList('branch_id,name');
       foreach($branch as $k=>$v){
            $tmp[] = $v;
        }
        $this->pagedata['branch']=$tmp;
       $this->display("admin/stock/importfile.html");
    }

/*以CSV导出*/
    function gettypecsv(){
        $filename = time();
        $oExport= &$this->app->model('system/export');
        $branch=$_POST['branch'];
        $str_array[]= array($branch,'bn','name','store','sku_property','weight','store_position');
        $oProducts=&$this->app->model('products');
        /*test*/
//        $pro=$oProducts->getList('bn,name,store,sku_property,weight');
//        foreach($pro as $k=>$v){
//           $str_array[]=$v;
//       }
       /*test*/
        $oExport->export_begin($str_array,'csv',$count);
        $oExport->export_finish();
   }

/*
 * 以CSV文件导入商品库存
 *
 */
    function import(){
        
        $oProducts=&$this->app->model('products');
        $oBranch= &$this->app->model('branch');
        $oPos = &$this->app->model('branch_pos');

        $time=time();
        if(substr($_FILES['upload1']['name'],-4)!='.csv'){
                echo "<script>parent.MessageBox.success('文件格式有误!');</script>";
                exit;
         }
         $tmp = $_FILES['upload1']['tmp_name'];

         $content1 = file_get_contents($_FILES['upload1']['tmp_name']);
         if(substr($content1,0,3)=="\xEF\xBB\xBF"){
            $content1 = substr($content1,3);    //去BOM头
         }

        $content1=mb_convert_encoding($content1,'UTF-8','GB2312');
        $handle1 = fopen('storedata','wb');//订单信息文件
        fwrite($handle1,$content1);
        fclose($handle1);
        $handle = fopen('storedata','r');
        $i=0;
          
         while($row1 = fgetcsv($handle, 1000, ",")){
            $i++;
            if($i == 1){
                $branch_id = $row1[0];
                $branch = $oBranch->dump($branch_id);
                if(empty($branch)){
                    echo "<script>parent.MessageBox.success('此仓库不存在');</script>";
                    exit;
                }
                 echo "<script>parent.MessageBox.success($row1[3]);</script>";
                if($row1[1] != 'bn' || $row1[2] !='name' || $row1[3] !='store' || $row1[4] !='sku_property' || $row1[5] !='weight' || $row1[6]!='store_position'){
                    echo "<script>parent.MessageBox.success('格式不正确');</script>";
                    exit;
                }

                continue;
            }
            $pos_id = $row1[6];
            $bn = $row1[1];


            $pro_s = array('bn'=>$bn);

            $product = $oProducts->dump(array('bn'=>$bn),'product_id');


            if(empty($product)){
                    echo "<script>parent.MessageBox.success('产品不存在不可导入');</script>";
                    exit;
            }
            $pos = $oPos->dump(array('branch_id'=>$branch_id,'store_position'=>$pos_id),'*');
            if(empty($pos)){
                 echo "<script>parent.MessageBox.success('货位不存在不可导入');</script>";
                 exit;
            }

       }
        
       $handle2 = fopen('storedata','r');
       $j=0;
       
        while($row = fgetcsv($handle2, 1000, ",")){

            $j++;
            if($j== 1){
                $branch_id = $row[0];
                continue;
            }
            $product_data=array('bn'=>$row[1],'name'=>$row[2],'store'=>$row[3],'sku_property'=>$row[4],'weight'=>$row[5],'branch_id'=>$branch_id,'type'=>$_POST['type'],'store_position'=>trim($row[6]));

           $oProducts->import_product($product_data);
       }

        echo "<script>parent.MessageBox.success('导入成功!');</script>";
        
    }
}


?>
