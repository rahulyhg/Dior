<?php

/**
 * 多进程封装类
 *
 *
 */
class proc
{
    /**
     * 子进程pid列表
     *
     * @var array
     */
    private $children_ids = array();

    /**
     * 子进程pid执行起始时间
     *
     * @var array
     */
    private $children_ids_start_time = array();

    /**
     * 同时执行的子进程数目上限
     *
     * @var integer
     */
    private $max_proc_num = 10;

    /**
     * 单个子进程的执行超时时间
     *
     * @var integer
     */
    private $expire_time = 1800;

    /**
     * 构造函数
     *
     * @param integer $max_proc_num 子进程数目上限
     */
    public function __construct($max_proc_num=0){
        if(intval($max_proc_num) > 0){
            $this->max_proc_num = intval($max_proc_num);
        }
    }

    /**
     * 获取子进程数目上限
     *
     * @return integer
     */
    public function get_max_proc_num()
    {
        return $this->max_proc_num;
    }

    /**
     * 设置子进程数目上限
     *
     * @param unknown_type $max_proc_num
     */
    public function set_max_proc_num($max_proc_num)
    {
        $this->max_proc_num = $max_proc_num;
    }

    /**
     * 杀死所有子进程
     *
     */
    public function kill_children()
    {
        foreach($this->children_ids as $pid){
            posix_kill($pid,SIGKILL);
        }
    }

    /**
     * 获取当前正在执行的子进程数目
     *
     * @return integer
     */
    public function get_running_proc_num()
    {
        return count($this->children_ids);
    }

    /**
     * 等待，直到正在执行的子进程数目不超过$num
     *
     * @param integer $num
     */
    public function wait_children_exit($num = 0)
    {
        //只要当前正在工作的子进程数目小于num,就停止循环
        while( count($this->children_ids) > $num ){
            $pid = pcntl_wait($status,WUNTRACED);
           //如果一个子进程退出了，就把子进程从pid列表中去掉。
            if($pid >0 && $k = array_search($pid,$this->children_ids)){
                unset($this->children_ids[$k]);
                unset($this->children_ids_start_time[$pid]);
            }
        }
    }

    /**
     * 杀死过期的子进程释放进程数
     *
     * @return integer
     */
    public function kill_expire_children(){
        foreach($this->children_ids as $k=>$v){
            if($this->children_ids_start_time[$v] + $this->expire_time < time()){
                if(posix_kill($v,SIGKILL)){
                    //echo "kill children".$v;
                    unset($this->children_ids[$k]);
                    unset($this->children_ids_start_time[$v]);
                }
            }
        }
    }

    /**
     * 使用子进程来执行一个方法
     *
     */
    public function run()
    {
        //如果有子进程数目限制，则等待，直到子进程数目小于上限
        if(count($this->children_ids) >= 1){
              $this->wait_children_exit($this->max_proc_num-1);
              $this->kill_expire_children();
        }
        //fork一个进程
        $pid = pcntl_fork();

        if($pid == -1){
            //创建子进程失败,可做输出或标记
            exit;
        }elseif($pid >0){
            //主进程
            $this->children_ids[$pid] = $pid;
            $this->children_ids_start_time[$pid] = time();
            return;
        }else{
            //子进程
            //取出方法和参数
            $args = func_get_args();
            $func = array_shift($args);

            //判断是否是可调用的方法，是则执行，否则报错。
            if(is_callable($func) == true){
                call_user_func_array($func,$args);
            }
            else{
                echo "the parameter is not callable \n";
                var_dump($func);
            }
            exit;
        }
    }
}
