<?php
/**
 * 自动任务入口类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_rpc_entrance{

    /**
     *
     * 静态私有变量系统级参数
     * @var array
     */
    static private $_sysParams = array();

    /**
     *
     * 静态私有变量应用级参数
     * @var array
     */
    static private $_appParams = array();

    /**
     *
     * 自动任务接口入口函数
     * @param array $params
     */
    public function service($params){
        $result = array('rsp'=>'fail','msg'=>'');

        //接收所有参数
        $this->setParams($params);

        //检查系统级参数
        if(!$this->checkSysParams($params)){
            $result['msg'] = 'no params';
            echo json_encode($result);
            exit;
        }

        //签名验证
        if(!$this->validate($params)){
            $result['msg'] = 'valid error';
            echo json_encode($result);
            exit;
        }

        //检查任务是否合法
        $allow_tasks = taskmgr_whitelist::get_all_task_list();
        if(isset($allow_tasks[self::$_sysParams['task_type']])){

            $className = $allow_tasks[self::$_sysParams['task_type']];
            if(class_exists($className)){

                $obj = kernel::single($className);
                if(method_exists($obj,'process')){
                    if($res = $obj->process(self::$_appParams, $error_msg)){
                        $result['rsp'] = 'succ';
                        echo json_encode($result);
                        exit;
                    }else{
                        $result['msg'] = $error_msg ? $error_msg : 'code error';
                        echo json_encode($result);
                        exit;
                    }
                }else{
                    $result['msg'] = 'no process method';
                    echo json_encode($result);
                    exit;
                }
            }else{
                $result['msg'] = 'no process lib';
                echo json_encode($result);
                exit;
            }

        }else{
            $result['msg'] = 'no defined task';
            echo json_encode($result);
            exit;
        }
    }

    /**
     *
     * 接收传入参数兼容post数据
     * @param unknown_type $params
     */
    private function setParams(&$params){
        //只认post方式过来的参数
        return $params = $_POST;
    }

    /**
     *
     * 检查系统级参数函数
     * @param array $params
     */
    private function checkSysParams($params){
        if(empty($params['task_type']) || empty($params['sign'])){
            return false;
        }

        self::$_sysParams['task_type'] = $params['task_type'];
        return true;
    }

    /**
     *
     * 验证签名函数
     * @param array $params
     */
    private function validate($params){

        $sign = $params['sign'];
        unset($params['sign']);
        $local_sign = taskmgr_rpc_sign::gen_sign($params);

        if(!$local_sign || $sign != $local_sign){
            return false;
        }else{
            unset($params['task_type']);

            self::$_appParams = $params;
            return true;
        }
    }

}
