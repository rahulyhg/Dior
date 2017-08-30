<?PHP
/**
 * 库存回写处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_inventorydepth
{
    public function process($params, &$error_msg=''){

        kernel::single('inventorydepth_logic_stock')->start();

        return true;
    }
}