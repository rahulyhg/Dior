<?php
class taoexlib_finder_queue{
    
	var $addon_cols = "status,is_resume";
    var $column_op='操作';
    var $column_op_width = "80";

    function column_op($row){
        $queue_id = $row['queue_id'];
        $find_id  = $_GET['_finder']['finder_id'];
        $button = <<<EOF
        <a href="index.php?app=taoexlib&ctl=queue&act=retry&p[0]=$queue_id&find_id=$find_id">重试</a>
EOF;
        if ($row[$this->col_prefix.'status'] == 'failed' && $row[$this->col_prefix.'is_resume'] == 'true'){
            return $button;
        }
    }
	
  /* 详情
     *
     */
    function detail_log($queue_id){
        $render = app::get('taoexlib')->render();
        $queueObj = &app::get('taoexlib')->model("queue");
        $queue = $queueObj->dump($queue_id);
		$queue['params'] = var_export($queue['params'],true);
		$errmsg = unserialize($queue['errmsg']);
		if(is_array($errmsg)){
			$queue['errmsg'] = implode('<br />', $errmsg);
		}else{
			$queue['errmsg'] = $errmsg;
		}
		
        $render->pagedata['queue'] = $queue;
        return $render->fetch("queue/detail.html");
    }
}