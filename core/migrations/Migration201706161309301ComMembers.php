<?php

use Hubzero\Content\Migration\Base;

/**
 * Migration script for modifying scope of #__users_log_auth.status
 **/
class Migration201706161309301 extends Base
{
	/**
	 * Up
	 **/
	public function up()
	{
		if ($this->db->tableHasField('#__users_log_auth', 'status'))
		{
			$query = "ALERT TABLE `#__users_log_auth` MODIFY COLUMN status ENUM('failure', 'success', 'blocked', 'released')";
			$this->db->setQuery($query);
			$this->db->query();
		}
	}

	/**
	 * Down
	 **/
	public function down()
	{
	}
}
