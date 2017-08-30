<?php
class inventorydepth_misc_task{

    /* 每分钟执行 */
    public function minute()
    {   return ;
        $now = time();

        /* 将最大可售库存变化的记缓存,如果是第一次执行全部记缓存 */

        # 最后一次统计时间
        base_kvstore::instance('inventorydepth/statistics')->fetch('max_store_change',$lastStatistics);
        if (!$lastStatistics) {
            # 不存在 存入所有
            kernel::single('inventorydepth_stock_products')->init();

            kernel::single('inventorydepth_stock_products')->set_branches();
        } else {
            # 只存入变化的
            $filter = array(
                'max_store_lastmodify|between' => array(
                    0 => $lastStatistics,
                    1 => $now,
                ),
            );
            kernel::single('inventorydepth_stock_products')->init($filter);
        }
        base_kvstore::instance('inventorydepth/statistics')->store('max_store_change',$now);

        //kernel::single('inventorydepth_logic_stock')->start();

        //kernel::single('inventorydepth_logic_frame')->start();

    }

    /* 每小时执行 */
    public function hour(){}

    /* 每天执行 */
    public function day(){}
    
    /* 每星期执行 */
    public function week(){}

    /* 月执行 */
    public function month(){}

}