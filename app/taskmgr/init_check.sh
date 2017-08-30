#!/bin/bash
# Name : php-cgi

phpcgiprocess=$(ps -ef |grep xterp_script|grep taskDaemon|grep init)

if [ -z "$phpcgiprocess" ];then
        echo "$(date)  init  downd  ">> /data/xterp_script/taskmgr/logs/phpdown.log
	rm -rf	/var/run/xt-taskDaemon-init.pid
	/usr/local/php-pthreads/bin/php  /data/xterp_script/taskmgr/taskDaemon.php start init >> /data/xterp_script/taskmgr/logs/phpdown.log
	echo "$(date) init ok" >> /data/xterp_script/taskmgr/logs/phpdown.log

else
        echo "$(date) init ok" >> /tmp/xt-chechphp.log
fi
