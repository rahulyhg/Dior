<?PHP
/**
 * 自动对账处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_financecronjob
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);

        //判断是否安装不安装直接返回成功
        if(!app::get('finance')->is_installed()){
            return true;
        }
        
        //一小时跑一次
        kernel::single('finance_cronjob_tradeScript')->trade_search_queue();
        kernel::single('finance_cronjob_tradeScript')->get_taskid_result();
        kernel::single('finance_cronjob_tradeScript')->taskid_queue();
        //kernel::single('finance_cronjob_tradeScript')->autoretry();
        kernel::single('finance_cronjob_tradeScript')->get_sales();
        //kernel::single('finance_cronjob_autoflagScript')->autoflag_queue();

        return true;
    }
}