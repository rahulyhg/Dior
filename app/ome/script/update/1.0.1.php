<?php
    //安装自动规则、捆绑商品、绩效考核app插件
    $shell->exec_command("install omeauto");
    $shell->exec_command("install omepkg");
    $shell->exec_command("install omekpi");