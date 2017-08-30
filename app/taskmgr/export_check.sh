#!/bin/bash
# Name : php-cgi

phpcgiprocess=$(ps -ef |grep xterp_script|grep taskDaemon|grep export)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  export  downd  ">> /data/xterp_script/taskmgr/logs/phpdown.log
	rm -rf	/var/run/xt-taskDaemon-export.pid
	/usr/local/php-pthreads/bin/php  /data/xterp_script/taskmgr/taskDaemon.php start export >> /data/xterp_script/taskmgr/logs/phpdown.log
	echo "$(date) export ok" >> /data/xterp_script/taskmgr/logs/phpdown.log

else
        echo "$(date) export ok" >> /tmp/xt-chechphp.log
fi
