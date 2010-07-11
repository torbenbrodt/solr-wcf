<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchableMessageType.class.php');
require_once(WCF_DIR.'lib/data/tag/TagEngine.class.php');

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
	 * @var string
	 */	
	protected $type = '';
	
	protected $additionalOuterSelects = '';
	protected $additionalInnerSelects = '';
	protected $outerJoins = '';
	
	/**
	 *
	 */
	protected $columns = array();

	/**
	 *
	 * @param	SearchableMessageType
	 */
	public function __construct($type, SearchableMessageType $inst) {
		$this->type = $type;
		$this->inst = $inst;
		
		// get table structure
		$this->addColumnsFromTable($inst->getTableName());

		if(preg_match_all("/[a-z]+\d+_[a-z]+/", $inst->getJoins(), $res)) {
			foreach($res[0] as $tableName) {
				$this->addColumnsFromTable($tableName);
			}
		}

		// join with taggable model?
		$sql = "SELECT		taggableID
			FROM 		wcf".WCF_N."_searchable_message_type
			INNER JOIN 	wcf".WCF_N."_tag_taggable USING(packageID)
			WHERE		typeName = '".escapeString($this->type)."'";
		$row = WCF::getDB()->getFirstRow($sql);
		if($row) {
			foreach(TagEngine::getInstance()->getTaggables() as $taggable) {
				if($taggable->getTaggableID() == $row['taggableID'] && $this->columnExists($taggable->getIDFieldName())) {
					$this->outerJoins .= ' LEFT JOIN wcf'.WCF_N.'_tag_to_object tag_to_object
						ON ( 
							tag_to_object.taggableID = '.$taggable->getTaggableID().'
							AND tag_to_object.objectID = messageTable.'.$taggable->getIDFieldName().'
						)
						LEFT JOIN  wcf'.WCF_N.'_tag tag
						ON (tag.tagID = tag_to_object.tagID)';

					$this->additionalOuterSelects .= ' GROUP_CONCAT(tag.name SEPARATOR " ") AS anchor ';
					$this->additionalInnerSelects .= ' messageTable.'.$taggable->getIDFieldName().' ';
					break;
				}
			}
		}
	}
	
	public function getAdditionalOuterSelects() {
		return $this->additionalOuterSelects;
	}
	
	public function getAdditionalInnerSelects() {
		return $this->additionalInnerSelects;
	}
	
	public function getOuterJoins() {
		return $this->outerJoins;
	}
	
	protected function addColumnsFromTable($tableName) {
		$sql = 'DESCRIBE `'.escapeString($tableName).'`';
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$this->columns[] = $row['Field'];
		}
	}
	
	protected function columnExists($columns) {
		$return = true;
		if(is_array($columns)) {
			foreach($columns as $column) {
				if(($x = strpos($column, '.')) !== false) {
					$column = trim(substr($column, $x + 1));
				}
				if(!in_array($column, $this->columns)) {
					$return = false;
					break;
				}
			}
		} else {
			$column = $columns;
			if(($x = strpos($column, '.')) !== false) {
				$column = trim(substr($column, $x + 1));
			}
			if(!in_array($column, $this->columns)) {
				$return = false;
			}
		}
		return $return;
	}
	
	/**
	 * pass magic method to owner object
	 */
	public function __call($method, $args) {
		
		if(preg_match('/FieldName[s]?$/', $method)) {
			$columns = call_user_func_array(array($this->inst, $method), $args);
			if(!$this->columnExists($columns)) {
				return null;
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
