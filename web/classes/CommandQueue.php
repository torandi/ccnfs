<?php
class CommandQueue extends BasicObject {
	protected static function table_name() {
		return 'command_queue';
	}

	public static function cleanup() {
		global $db;
		$stmt = $db->prepare("DELETE FROM command_queue WHERE time < (NOW() - INTERVAL 5 MINUTE)");
		$stmt->execute();
	}
}
