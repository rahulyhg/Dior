<?PHP
/**
 * 导出任务拆分处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_exportsplit
{

    //当前导出app
    static private $__app = '';

    //当前导出model
    static private $__model = '';

    //当前导出过滤条件
    static private $__filter = array();

    //当前导出任务号
    static private $__task_id = '';

    //当前导出是否可以拆分
    static private $__cansplit = false;

    //分片任务处理队列类型
    static private $__queue_type = 'normal';

    //当前导出数据源
    static private $__data_source = '';

    //当前导出数据源配置
    static private $__data_source_cnf = array();

    //导出字段
    static private $__fields ='';

    //导出内容是否包含明细结构内容
    static private $__has_details = 2;

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

        //检查参数是否有效，不然直接返回
        if(!array_key_exists($date_source, ome_export_whitelist::allowed_lists())){
            return false;
        }else{
            self::$__app = $params['app'];
            self::$__model = $params['model'];
            self::$__task_id = $params['task_id'];
            self::$__filter = unserialize($params['filter_data']);
            self::$__data_source = $date_source;
            self::$__data_source_cnf = ome_export_whitelist::allowed_lists($date_source);
            self::$__cansplit = self::$__data_source_cnf['cansplit'] > 0 ? true : false;
            self::$__queue_type = $params['queue_type'];
        }

        //标记当前导出任务开始执行
        $ietaskObj = app::get('taoexlib')->model('ietask');
        $ietask_data = array('status' => 'running','task_id' => self::$__task_id);
        $ietaskObj->save($ietask_data);
        unset($ietask_data);

        //检查当前导出任务的拆分方式
        if(self::$__cansplit){
            //可以拆分的
            $exportObj = app::get(self::$__app)->model(self::$__model);
            //检查当前导出对象是否支持导出字段定义
            if($exportObj->has_export_cnf){
                if(self::$__filter['_export_mb'] == 1){
                    //自定义字段
                    self::$__fields = self::$__filter['export_fields'];
                    self::$__has_details = self::$__filter['need_detail'];
                    unset(self::$__filter['_export_mb'], self::$__filter['export_fields'], self::$__filter['need_detail']);
                }elseif(self::$__filter['_export_mb'] == 2){
                    //已有模板字段
                    $exptempObj = app::get('desktop')->model('export_template');
                    $tempInfo = $exptempObj->getList('et_filter',array('et_id'=>self::$__filter['extemp_id']),0,1);
                    $curr_filter = unserialize($tempInfo[0]['et_filter']);
                    self::$__fields = $curr_filter['fields'];
                    self::$__has_details = $curr_filter['need_detail'];
                    unset($tempInfo,$curr_filter,self::$__filter['extemp_id']);
                }
            }

            //判断如果非多层结构那默认定义导出数据只有一层,发货单是单层结构的主＋明细数据
            if(self::$__data_source_cnf['structure'] == 'single'){
                self::$__has_details = 2;
            }

            //统计当然导出数据源的总数进行切分
            //$count = $exportObj->count(self::$__filter);
            //$split_num = ceil($count/self::$__data_source_cnf['splitnums']);

            //加载存储介质
            $cacheLib = kernel::single('taskmgr_interface_cache',self::$__task_id);

            //任务计数器，分片任务完成后与总任务进行比较
            $cacheLib->store('exp_task_'.self::$__task_id.'_counter',0,self::$__ttl);

            //导出数据记录数统计
            $cacheLib->store('exp_task_'.self::$__task_id.'_records',0,self::$__ttl);
            
            //队列的类型快、慢之分已在任务生成时标记
            if(self::$__queue_type == 'normal'){
                $queue_name = 'dataquerybysheet';
            }elseif(self::$__queue_type == 'quick'){
                $queue_name = 'dataquerybyquicksheet';
            }

            //开启高级搜索
            $exportObj->filter_use_like = true;

            //判断分片任务是否可按主键分片还是直接根据结果数量进行分片
            if(isset(self::$__data_source_cnf['primary_key'])){
                //一次性取所有当然导出的数据的主键id数据
                @ini_set('memory_limit', '1024M');

                //判断主键id数据是否通过自定义查询方法获取,不然走框架方法获取
                $primary_ids = array();
                if(method_exists($exportObj, 'getPrimaryIdsByCustom')){
                    $primary_ids = $exportObj->getPrimaryIdsByCustom(self::$__filter);
                }else{
                    $primary_info = $exportObj->getList(self::$__data_source_cnf['primary_key'], self::$__filter, 0, -1);
                    if($primary_info){
                        foreach($primary_info as $info){
                            $primary_ids[] = $info[self::$__data_source_cnf['primary_key']];
                        }
                    }
                }

                //根据取出的主键ids以及配置每多少个切分数量计算切分出来的任务数
                $split_num = ceil(count($primary_ids)/self::$__data_source_cnf['splitnums']);

                $now_sheet = 1;
                $now_nums = 0;
                if($primary_ids){
                    $filter_ids = array();
                    $ids_list = array_chunk($primary_ids, self::$__data_source_cnf['splitnums']);

                    foreach ($ids_list as $key => $ids) {
                        $filter_ids[self::$__data_source_cnf['primary_key']] = $ids;

                        $push_params = array(
                            'data' => array(
                                'filter' => serialize($filter_ids),
                                'fields' => self::$__fields,
                                'has_detail' => self::$__has_details,
                                'task_id' => self::$__task_id,
                                'app' => self::$__app,
                                'model' => self::$__model,
                                'curr_sheet' => $now_sheet,
                                'sheet_sum' => $split_num,
                                'task_type' => $queue_name
                            ),
                            'url' => kernel::openapi_url('openapi.autotask','service')
                        );
                        kernel::single('taskmgr_interface_connecter')->push($push_params);

                        unset($filter_ids);
                        $now_sheet++;
                    }
                }else{
                    $error_msg = 'search primary ids is empty';
                    return false;
                }
            }else{
                //没有主键是复杂链接查询的结果，按结构总数分片
                if(method_exists($exportObj, 'fcount_csv')){
                    $count = $exportObj->fcount_csv(self::$__filter);
                }else{
                    $count = $exportObj->count(self::$__filter);
                }

                if($count > 0){
                    //根据结果集总数分片的任务数
                    $split_num = ceil($count/self::$__data_source_cnf['splitnums']);
                    
                    $filter_arr = array();
                    for($now_sheet=1;$now_sheet<=$split_num;$now_sheet++) {
                        $start = ($now_sheet-1)*self::$__data_source_cnf['splitnums'];
                        $end = self::$__data_source_cnf['splitnums'];

                        $push_params = array(
                            'data' => array(
                                'filter' => serialize(self::$__filter),
                                'start' => $start,
                                'end' => $end,
                                'fields' => self::$__fields,
                                'has_detail' => self::$__has_details,
                                'task_id' => self::$__task_id,
                                'app' => self::$__app,
                                'model' => self::$__model,
                                'curr_sheet' => $now_sheet,
                                'sheet_sum' => $split_num,
                                'task_type' => $queue_name
                            ),
                            'url' => kernel::openapi_url('openapi.autotask','service')
                        );
                        kernel::single('taskmgr_interface_connecter')->push($push_params);
                    }
                }else{
                    $error_msg = 'search result is empty';
                    return false;
                }
            }
        }else{
            //不可拆分的，走一次性慢悠悠查询，区分处理任务队列，如盘点
            $push_params = array(
                'data' => array(
                    'filter' => serialize(self::$__filter),
                    'task_id' => self::$__task_id,
                    'app' => self::$__app,
                    'model' => self::$__model,
                    'task_type' => 'dataquerybywhole'
                ),
                'url' => kernel::openapi_url('openapi.autotask','service')
            );
            kernel::single('taskmgr_interface_connecter')->push($push_params);

        }
        return true;
    }

}