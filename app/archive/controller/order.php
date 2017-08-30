<?php
class archive_ctl_order extends desktop_controller{
    var $workground = "order_center";
    /**
     * 归档设置
     * @param  
     * @return 
     * @access  
     * @author sunjing@shopex.cn
     */
    function index()
    {
        

        $this->page("set.html");
    }
    
    /**
     * 归档查询
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function search()
    {
       
        if(empty($_POST['time_from'])){
            $_POST['time_from'] = date("Y-m-1");
            $_POST['time_to'] = date("Y-m-d",time()-24*60*60);
        }
        //$_POST['flag'] = isset($_POST['flag']) ? $_POST['flag'] : 0;
        
        kernel::single('archive_data')->set_params($_POST)->display();
    }

    
    
    
    function ajaxGetArchiveData()
    {
        $archivelib = kernel::single('archive_order');
        $orderfilter = $_GET;
        $this->pagedata['currentTime'] = time();
        $total = $archivelib->get_total($orderfilter);
        $this->pagedata['total'] = $total;
        $this->pagedata['params'] = $orderfilter;
        $this->pagedata['pagenum'] = ceil($total/500);
        $activehouse = date('H');
        
        if ($activehouse<21 && $activehouse>9) {
            echo "当前时间不可以操作归档";
            exit;
        }
        $this->display('archive.html');
    }

    function saveset()
    {
      
        error_reporting(E_ALL);
        set_time_limit(0);
        $orderfilter = $_POST;
        $activehouse = date('H');
        $rs  = array('rsp'=>'succ','msg'=>'归档完成');
        
        $archivelib = kernel::single('archive_order');
        $data = $archivelib->get_order($orderfilter);
        echo json_encode($rs);
        
        
    }

    
    /**
     * 执行归档
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function ajaxDoAuto()
    {
        set_time_limit(0);
        $params = $_POST['ajaxParams'];
        $archivelib = kernel::single('archive_order');
        $result = $archivelib->process($params);
        echo true;
    }
    
   
    
    /**
     * 格式化归档时间.
     * @param  
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function format_archivetime()
    {
        $archive_time = $_POST['archive_time'];
        
        switch($archive_time){
            case '1':
                $archive_time =  strtotime("-1 month");
            break;
            case '2':
                $archive_time =  strtotime("-2 month");
            break;
             case '3':
                $archive_time =  strtotime("-3 month");
            break;
            case '6':
                $archive_time =  strtotime("-6 month");
            break;
            case '9':
                $archive_time =  strtotime("-9 month");
            break;
            default:
                $archive_time =  strtotime("-12 month");
                break;
        }
        echo date('Y-m-d H:i:s',$archive_time);
    }

    
    function testshow(){
        $params = array(
                'title'=>'归档列表',
       
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>true,

                'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',

           );

       $this->finder('archive_mdl_orders',$params);
   }

   
 
}

?>