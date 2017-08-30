<?PHP
/**
 * 导出任务合并写文件处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_createfile
{

    //导出数据的存储过期时效
    static private $__task_id = 0;

    //任务分片数
    static private $__sheet_sum = 1;

    //任务是否含明细
    static private $__has_detail = 2;

    //导出任务总记录数
    static private $__records = 0;

    public function process($params, &$error_msg=''){
        set_time_limit(0);

        //根据传入参数查询具体数据
        self::$__task_id = $params['task_id'];
        self::$__sheet_sum = $params['sheet_sum'];
        self::$__has_detail = $params['has_detail'];
        self::$__records = $params['records'];

        //加载存储介质
        $cacheLib = kernel::single('taskmgr_interface_cache',self::$__task_id);
        $charset = kernel::single('base_charset');

        $path = DATA_DIR.'/export/tmp_remote';
        $filename = $path."/".md5(microtime().KV_PREFIX).self::$__task_id.'.csv';
        $handle = fopen($filename, "a");

        for($i=1;$i<=self::$__sheet_sum;$i++) {
            if($cacheLib->fetch('exp_body_main_'.self::$__task_id.'_'.$i,$main_content)){
                fwrite($handle,$main_content);
            }else{
                $error_msg = 'No '.$i.' main sheet no data or can\'t get data';
                fclose($handle);
                @unlink($filename);
                return false;
            }
        }

        if(self::$__has_detail == 1){
            for($i=1;$i<=self::$__sheet_sum;$i++) {
                if($cacheLib->fetch('exp_body_pair_'.self::$__task_id.'_'.$i,$pair_content)){
                    fwrite($handle,$pair_content);
                }else{
                    $error_msg = 'No '.$i.' pair sheet no data or can\'t get data';
                    fclose($handle);
                    @unlink($filename);
                    return false;
                }
            }
        }

        fclose($handle);

        //临时文件生成后往ftp服务器迁移
        $storageLib = kernel::single('taskmgr_interface_storage');
        $move_res = $storageLib->save($filename, self::$__task_id, $remote_url);
        if($move_res){
            //保存任务执行的相关结果信息
            //改成update方式，有的客户嫌慢直接把任务删了，save方式有问题
            $ietaskObj = app::get('taoexlib')->model('ietask');

            $ietask_data = array(
                'file_name' => $remote_url,
                'total_count' => self::$__records,
                'last_time' => time(),
                'expire_time' => strtotime(date('Ymd'))+3*86400,
                'status' => 'finished',
            );
            $ietaskObj->update($ietask_data, array('task_id' => self::$__task_id));

            //删除临时数据缓存，主要针对是本地文件形式的
             $cacheLib->delete('exp_task_'.self::$__task_id.'_counter');
             $cacheLib->delete('exp_task_'.self::$__task_id.'_records');

            for($i=1;$i<=self::$__sheet_sum;$i++) {
                $cacheLib->delete('exp_body_main_'.self::$__task_id.'_'.$i);
            }

            if(self::$__has_detail == 1){
                for($i=1;$i<=self::$__sheet_sum;$i++) {
                    $cacheLib->delete('exp_body_pair_'.self::$__task_id.'_'.$i);
                }
            }

            //删除导出内容在本地生成的临时文件
            @unlink($filename);
        }else{
            $error_msg = 'remote create file fail';
            //删除导出内容在本地生成的临时文件
            @unlink($filename);
            //标记当前任务临时文件生成但是没迁移成功
            return false;
        }

        return true;
    }

}