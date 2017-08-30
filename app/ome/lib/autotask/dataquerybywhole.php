<?PHP
/**
 * 一次性查询任务处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_dataquerybywhole
{
    //当前导出app
    static private $__app = '';

    //当前导出model
    static private $__model = '';

    //当前导出过滤条件
    static private $__filter = array();

    //当前导出任务号
    static private $__task_id = '';

    //当前导出数据源配置
    static private $__data_source_cnf = array();

    //导出任务总分片数
    static private $__sheet_sum = 1;

    //导出数据的存储过期时效
    static private $__expire_time = 86400;

    //最终过期的时间点
    static private $__ttl = '';

    public function __construct(){
        //当前存储的文件过期时间
        self::$__ttl = time() + self::$__expire_time;
    }

    public function process($params, &$error_msg=''){
        set_time_limit(0);

        $date_source = $params['app'].'_mdl_'.$params['model'];

        //根据传入参数查询具体数据
        self::$__app = $params['app'];
        self::$__model = $params['model'];
        self::$__filter = unserialize($params['filter']);
        self::$__data_source_cnf = ome_export_whitelist::allowed_lists($date_source);
        self::$__task_id = $params['task_id'];
        self::$__sheet_sum = 1;

        $exportObj = app::get(self::$__app)->model(self::$__model);

        //定义model调用的字符类
        $exportObj->charset = kernel::single('base_charset');

        //根据当前导出对象获取导出数据，判断是否是自定义方法
        $data = array();
        $exportObj->fgetlist_csv($data, self::$__filter, 0);

        //加载存储介质
        $cacheLib = kernel::single('taskmgr_interface_cache',self::$__task_id);

        //具体数据存储storage
        if(isset($data['content']['main']) && is_array($data['content']['main'])){

            if(count($data['content']['main']) > 500){
                //数据分片总数
                self::$__sheet_sum = ceil(count($data['content']['main'])/self::$__data_source_cnf['splitnums']);

                $tmp_arrs = array_chunk($data['content']['main'], self::$__data_source_cnf['splitnums']);
                //error_log(var_export($tmp_arrs,true),3,'/www/tmparr.log');
                if($tmp_arrs){

                    //当前数据分片
                    $curr_sum = 1;
                    foreach ($tmp_arrs as $key => $tmp_arr) {
                        //合并数据
                        $main_content = '';
                        foreach($tmp_arr as $v) {
                            $main_content .= $v."\n";
                        }

                        //总数据含标题小于500条,一次性临时保存
                        $cacheLib->store('exp_body_main_'.self::$__task_id.'_'.$curr_sum,$main_content,self::$__ttl);
                        $curr_sum++;
                    }
                }
            }else{
                $main_content = '';
                foreach($data['content']['main'] as $v) {
                    $main_content .= $v."\n";
                }

                //总数据含标题小于500条,一次性临时保存
                $cacheLib->store('exp_body_main_'.self::$__task_id.'_1',$main_content,self::$__ttl);
            }
        }

        //取总记录数
        //如果定义了就取定义的了，没有就count数组取
        $records = isset($data['records']) ? $data['records'] : count($data['content']['main']);

        //判断是否所有分片任务都完成，完成往归档任务队列添加任务
        $push_params = array(
            'data' => array(
                'task_id' => self::$__task_id,
                'sheet_sum' => self::$__sheet_sum,
                'records' => $records,
                'has_detail' => 2,
                'task_type' => 'createfile'
            ),
            'url' => kernel::openapi_url('openapi.autotask','service')
        );
        kernel::single('taskmgr_interface_connecter')->push($push_params);

        return true;
    }

}