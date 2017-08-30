<?php
class ome_ctl_admin_reship extends desktop_controller{
    var $name = "退货单";
    var $workground = "invoice_center";

    function index(){
       $this->finder('ome_mdl_reship',array(
            'title' => '退货单管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }


     
     /**
      * Short description.
      * @param   type    $varname    description
      * @return  type    description
      * @access  public
      * @author cyyr24@sina.cn
      */
     function test()
     {
         $reshipObj = app::get('ome')->model('reship');
           foreach( $reshipObj ->io_title('reship') as $k => $v ){
                $title[] = $v;
            }
         $item = $reshipObj->oSchema['csv']['items'];
         echo '<pre>';
         //$item = $item['csv']['items'];
        // print_r($reshipObj->ioTitle);
         print_r($item);
     }
}

?>
