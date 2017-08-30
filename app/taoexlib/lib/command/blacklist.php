<?php
class taoexlib_command_blacklist extends base_shell_prototype {
	public $command_update = 'Update Blacklist';
	public function command_update() {
		taoexlib_utils::update_blacklist();
	}
}