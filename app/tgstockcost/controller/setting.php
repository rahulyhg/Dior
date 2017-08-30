<?php
/**
 * 库存成本设置CTL
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class tgstockcost_ctl_setting extends desktop_controller
{
    public function dialogSetting()
    {
        $this->pagedata['tgstockcost']['setting']['cost']['options'] = kernel::single('tgstockcost_system_setting')->get_tgstockcost_cost();
        $this->pagedata['tgstockcost']['setting']['cost']['value'] = kernel::single('tgstockcost_system_setting')->get_setting_value('tgstockcost.cost');

        $this->pagedata['tgstockcost']['setting']['get_value_type']['options'] = kernel::single('tgstockcost_system_setting')->get_tgstockcost_get_value_type();
        $this->pagedata['tgstockcost']['setting']['get_value_type']['value'] = kernel::single('tgstockcost_system_setting')->get_setting_value('tgstockcost.get_value_type');

        $this->display('admin/system/setting/dialogset.html');
    }

    public function save()
    {
        $this->begin();

        $rs = kernel::single('tgstockcost_system_setting')->setting_save($_POST['extends_set'],$msg);

        $this->end($rs,$msg);
    }

    public function initial()
    {
        $this->begin();
        $setting = kernel::single('tgstockcost_system_setting')->get_setting_value();
        // 写LOG
        $oplogModel = app::get('tgstockcost')->model('operation');
        $_tgcost['tgstockcost_cost'] = $setting['tgstockcost.cost'];
        $_tgcost['tgstockcost_get_value_type'] = $setting['tgstockcost.get_value_type'];
        $_tgcost['install_time'] = time();
        $_tgcost['op_id'] = kernel::single('desktop_user')->get_id();
        $_tgcost['op_name'] = kernel::single('desktop_user')->get_name();
        $_tgcost['operate_time'] = time();
        // $_tgcost['status'] = '1';//当前成本法
        $_tgcost['type'] = '2';

        $oplogModel->save($_tgcost);

        kernel::single("tgstockcost_instance_router")->create_queue();

        $this->end(true,'期初设置成功');
    }
}