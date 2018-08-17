<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/4/25
 * Time: 11:55
 */
class creditorderapi_api_sitesftp extends creditorderapi_api{

    //返回信息
    public $error_msg='';

    public function __construct(){
        set_time_limit(0);
        ini_set("memory_limit","128M");
        ini_set("max_execution_time",0);
        $this->sftp_lib=kernel::single('omeftp_sftp');
    }
    public function crontab_update_price($shop_id=''){
        if(empty($shop_id)){
            $shop_list=$this->get_shop_list();
        }else{
            $shop_list[]=$shop_id;
        }
        if(empty($shop_list)){
            $this->error_msg='shop_id为空';
            return false;
        }
        foreach($shop_list as $val){
            $ax_code=app::get('omeftp')->model('apiconfig')->getList('ax_code',array('shop_id'=>$val));
            $request_time=microtime(true);
            $this->error_msg='';
            if($this->sftp_connect()){
                $this->read_dir($ax_code[0]['ax_code']);
            }
            //日志数据组装
            if(empty($this->error_msg)){
                $status='success';
                $response_data='价格文件同步成功';
            }else{
                $status='fail';
                $response_data=$this->error_msg;
            }
            $data = array(
                'api_handler'=>'request',
                'api_name'=>'sftp.update.price',
                'api_status'=>$status,
                'api_request_time'=>$request_time,
                'api_check_time' => time(),
                'http_runtime'=>sprintf('%.6f',microtime(true)-$request_time),
                'http_method'=>'SFTP',
                'http_response_status'=>'200',
                'http_url'=>'NULL',
                'http_request_data'=>$val,
                'http_response_data'=>$response_data,
                'sys_error_data'=>'NULL'
            );
            app::get('creditorderapi')->model('api_log')->save($data);
            //如超过两天未收到价格文件则发送邮件提醒
            if($status=='fail'){
                $last_fail_time=app::get('creditorderapi')->getconf($val.'last_fail_time');
                if(empty($last_fail_time)){
                    app::get('creditorderapi')->setconf($val.'last_fail_time',time());
                }else{
                    $timeout=time()-$last_fail_time;
                    $max_time=3600*24*2;
                    if($timeout>=$max_time){
                        $shop_bn=app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$val));
                        $acceptor=app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                        $subject='【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】价格文件更新超期提醒';
                        $bodys='价格文件已经超过两天没有更新了,请注意检查!';
                        kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
                    }
                }
            }else{
                app::get('creditorderapi')->setconf($val.'last_fail_time',null);
            }
        }
        return true;
    }

    //检查文件时间
    public function check_time($time){
//        $file_time=strtotime($time);
//        $begin_today=mktime(0,0,0,date('m'),date('d'),date('Y'));
//        $end_today=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
//        if($file_time>$begin_today && $file_time<$end_today){
//            return true;
//        }
//        return false;
        return true;

    }


    //sftp服务器连接
    public function sftp_connect(){
        $sftp_info=app::get('omeftp')->getConf("ftp_service_setting");
        if(empty($sftp_info)){
            $this->error_msg='sftp配置信息读取失败';
            return false;
        }
        $this->remote_dir=$sftp_info['dir'];

        $sftp_sfg = array(
            'host'=>$sftp_info['host'],
            'port'=>$sftp_info['port'],
            'name'=>$sftp_info['name'],
            'pass'=>$sftp_info['pass'],
        );
        if($this->sftp_lib->connect($sftp_sfg,$this->error_msg)){
            return true;
        }else{
            return false;
        }
    }


    //拷贝sftp目录中的对应文件到本地
    public function read_dir($code){
        $local_dir=DATA_DIR.'/priceupdate/';
        if(!is_dir($local_dir)){
            mkdir($local_dir,'0777',true);
        }
        $date=date('Y-m-d',time());
        $file_list=$this->sftp_lib->get_list($this->remote_dir);
        //查找目标文件
        foreach($file_list as $filename){
            $file_arr=explode('_',$filename);
            if($file_arr[2]==$code && $this->check_time($file_arr[0])){
                $target_file[]=$filename;
                if(count($target_file)==2){
                    break;
                }
            }
        }
        //拷贝目标文件到本地
        if(empty($target_file) || count($target_file)!=2){
            $this->error_msg='未找到远端价格同步文件';
            return false;
        }else{
            foreach($target_file as $key=>$val){
                if(strpos($val,'dat')){
                    $final_file=$val;
                    break;
                }
            }
        }
        $local_filepath=$local_dir.$final_file;
        $sftp = intval( $this->sftp_lib->sftp);
        $remote_log_dir = "ssh2.sftp://" . $sftp.$this->remote_dir;
        $copy_res=copy($remote_log_dir.$final_file,$local_filepath);
        if(!$copy_res){
            $this->error_msg='文件拷贝失败';
            return false;
        }else{
            $res=$this->read_file($local_filepath);
            //删除sftp服务器端的文件
            foreach($target_file as $sftp_file){
                @unlink($remote_log_dir.$sftp_file);
            }
            if($res){
                return true;
            }
        }
        return false;

    }
    //读取本地文件内容
    public function read_file($file_name){
        if($handle=fopen($file_name,'r')){
            while(!feof($handle)){
                //如果fgets不写length参数，默认是读取1k。
                $data[]=fgets($handle);
                //每次处理100条数据防止内存溢出
                if(count($data)>=100 || feof($handle)){
                    $this->update_price($data);
                    $data=array();
                }
            }
            fclose($handle);
            return true;
        }else{
            $this->error_msg='本地文件读取失败';
            return false;
        }
    }

    //更新商品价格入口方法
    public function update_price($data){
        if(!empty($data)){
            foreach($data as $k=>$v){
                $line_data=explode('|',$v);
                if(count($line_data)<10){
                    continue;
                }
                $time_from=strtotime($line_data[14]);
                $time_to=strtotime($line_data[15]);
                $now_time=time();
                //校验有效时间
                if($now_time<=$time_from || $now_time>=$time_to){
                    continue;
                }
                $price=$line_data[19];
                $sku=$line_data[5];
                $res=$this->do_update_price($sku,$price);
            }
        }
    }
    //商品价格执行方法
    public function do_update_price($sku,$price){
        $bm_id=app::get('material')->model('basic_material')->getList('bm_id',array('material_bn'=>$sku));
        $sm_id=app::get('material')->model('sales_material')->getList('sm_id',array('sales_material_bn'=>$sku));
        if(empty($sm_id) || empty($bm_id)){
            return false;
        }
        $bm_res=app::get('material')->model('basic_material_ext')->update(array('retail_price'=>$price),array('bm_id'=>$bm_id[0]['bm_id']));
        $sm_res=app::get('material')->model('sales_material_ext')->update(array('retail_price'=>$price),array('sm_id'=>$sm_id[0]['sm_id']));
        if(!empty($bm_res) && !empty($sm_res)){
            return true;
        }
        return false;
    }

    //获取所有店铺的shop_id
    public function get_shop_list(){
        $shop_data=app::get('ome')->model('shop')->getList('shop_id');
        $shop_list=array();
        foreach($shop_data as $k=>$v){
            $shop_list[]=$v['shop_id'];
        }
        return $shop_list;
    }

}