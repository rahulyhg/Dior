<?php
/**
 * Created by PhpStorm.
 * User: august.yao
 * Date: 2018/08/07
 * Time: 13:40
 */
class ome_finder_kafka_api_log
{
    var $detail_basic   = '基本信息';
    var $column_control = '操作';

    function detail_basic($id)
    {
        $render   = app::get('ome')->render();
        $log_data = app::get('ome')->model('kafka_api_log')->dump(array('id' => $id), '*');
        $render->pagedata['data'] = $log_data;
        return $render->fetch('admin/kafka/detail.html');
    }

    public $column_operator       = '操作(重推)';
    public $column_operator_order = 1;
    public $column_operator_width = 70;
    public function column_operator($row)
    {
        $return = '';
        # 判断是否显示重新推送按钮
        if ($row['api_status'] == 'fail') {
            $src = app::get('desktop')->res_full_url.'/bundle/download.gif';
            $return .= <<<EOF
            <a style="margin:20px;padding:5px;background:url('{$src}') no-repeat scroll 50% 50%;" href='index.php?app=ome&ctl=admin_kafka_log&act=repeat&id={$row['id']}' title="重新推送Kafka"></a>
EOF;
        } else {
            $return .= <<<EOF
            <a style="margin:5px;padding:5px;"></a>
EOF;
        }

        return $return;
    }

}
