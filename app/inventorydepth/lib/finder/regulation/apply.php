<?php
class inventorydepth_finder_regulation_apply{

    public $column_operator = '操作';
    public $column_operator_order = 1;
    public function column_operator($row)
    {
        
        $finder_id = $_GET['_finder']['finder_id'];

        $button = <<<EOF
        <a target='_blank' href='index.php?app=inventorydepth&ctl=regulation_apply&act=edit&p[0]={$row['id']}&_finder[finder_id]={$finder_id}'>编辑</a>
EOF;
        return $button;
    } 

}
