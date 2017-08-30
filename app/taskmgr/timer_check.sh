#!/bin/bash
# Name : php-cgi

phpcgiprocess=$(ps -ef |grep xterp_script|grep taskDaemon|grep timer)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  timer  downd  ">> /data/xterp_script/taskmgr/logs/phpdown.log
	rm -rf  /var/run/xt-taskDaemon-timer.pid
	/usr/local/php-pthreads/bin/php  /data/xterp_script/taskmgr/taskDaemon.php start timer >> /data/xterp_script/taskmgr/logs/phpdown.log
	echo "$(date) timer ok" >> /data/xterp_script/taskmgr/logs/phpdown.log

else
        echo "$(date) timer ok" >> /tmp/xt-chechphp.log
fi
