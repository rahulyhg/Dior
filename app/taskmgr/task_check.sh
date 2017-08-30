#!/bin/bash
# Name : php-cgi

phpcgiprocess=$(ps -ef |grep php|grep xterp_script|grep -v timer|grep -v init|grep -v export|grep task)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  task  downd  ">> /data/xterp_script/taskmgr/logs/phpdown.log
	rm -rf	/var/run/xt-taskDaemon-task.pid
	/usr/local/php-pthreads/bin/php  /data/xterp_script/taskmgr/taskDaemon.php start task >> /data/xterp_script/taskmgr/logs/phpdown.log
	echo "$(date) task ok" >> /data/xterp_script/taskmgr/logs/phpdown.log

else
        echo "$(date) task ok" >> /tmp/xt-chechphp.log
fi
