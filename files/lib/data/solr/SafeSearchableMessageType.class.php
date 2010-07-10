<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchableMessageType.class.php');

/**
 * safe wrapper for checking if columns really exist
 */
class SafeSearchableMessageType {

	/**
	 *
	 * @var SearchableMessageType
	 */	
	protected $inst = null;
	
	/**
	 *
	 */
	protected $columns = array();

	/**
	 *
	 * @param	SearchableMessageType
	 */
	public function __construct(SearchableMessageType $inst) {
		$this->inst = $inst;
		
		$tableName = $inst->getTableName();
		
		// get table structure
		$sql = 'DESCRIBE `'.escapeString($tableName).'`';
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$this->columns[] = $row['Field'];
		}

		// TODO: query joins for safe columns
		// $joins = $inst->getJoins();
	}
	
	/**
	 * pass magic method to owner object
	 */
	public function __call($method, $args) {
		
		if(preg_match('/FieldName[s]?$/', $method)) {
			$columns = call_user_func_array(array($this->inst, $method), $args);
			if(is_array($columns)) {
				foreach($columns as $column) {
					if(($x = strpos($column, '.')) !== false) {
						$column = trim(substr($column, $x + 1));
					}
					if(in_array($column, $this->columns)) {
						return null;
					}
				}
			} else {
				$column = $columns;
				if(($x = strpos($column, '.')) !== false) {
					$column = trim(substr($column, $x + 1));
				}
				if(in_array($column, $this->columns)) {
					return null;
				}
			}
		}
	
		return call_user_func_array(array($this->inst, $method), $args);
	}

	/**
	 * pass magic method to owner object
	 */
	public function __get($name) {
		return $this->inst->$name;
	}

	/**
	 * pass magic method to owner object
	 */
	public function __set($name, $value) {
		$this->inst->$name = $value;
	}
}
?>
