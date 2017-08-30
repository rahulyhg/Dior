<?php
    class logisticsaccounts_ctl_admin_statements_obj extends desktop_controller{
        function addobj(){

            $this->page('add_statements_object.html');
        }

        function dosave(){
            $Ostatements_obj = $this->app->model('statements_object');
            $data = $_POST;
            $statements_obj = $Ostatements_obj->dump(array('name'=>$data['name']),'name');
            if($statements_obj){
               echo 'false';
               exit;
            }else{
                $obj_data = array(
                    'name'=>$data['name'],
                    'memo'=>$data['memo']
                );
                $result = $Ostatements_obj->save($obj_data);
                if($result){
                    echo json_encode($obj_data);
                }else{
                    echo 'false';
                }

            }
        }

    /**
     * 根据name快速获取订单信息
     *
     * @return void

     **/
    function getObjinfo(){
        $name = $_GET['name'];
          if ($name){
             $Ostatements_obj = $this->app->model('statements_object');

            $statements_obj = $Ostatements_obj->getlist('name,memo',array('name|has'=>$name),0,-1);
            echo "window.autocompleter_json=".json_encode($statements_obj);
        }

    }



    }
?>