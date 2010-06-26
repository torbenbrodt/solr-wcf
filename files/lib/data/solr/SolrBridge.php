<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchEngine.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');


/**
 *
 */	
class SolrBridge {
	
	/**
	 * 
	 * @var array<Apache_Solr_Document>
	 */
	protected $documents = array();
	
	/**
	 *
	 * @var SolrService
	 */
	protected $solr = null;
	
	/**
	 *
	 */
	public function __construct() {
	
		// load search type objects
		SearchEngine::getSearchTypes();
		
		$this->solr = new SolrService();
	}
	
	/**
	 *
	 */
	protected function commit() {
		$this->solr->addDocuments( $this->documents );
		$this->solr->commit();
		
		// mark items as done
		$sql = "INSERT IGNORE INTO
					wcf".WCF_N."_solr_index
					(typeID, messageID)";
		foreach($this->documents as $doc) {
			$sql .= ""; //TODO: mark as done
		}
		$result = WCF::getDB()->sendQuery($sql);
		
		// reset array
		$this->documents = array();
		
		$this->solr->optimize();
	}
	
	/**
	 *
	 */
	protected function getTotals(array $types, $func) {

		$sql = '';
		foreach ($types as $type) {

			// get search type object
			$doc = SearchEngine::$searchTypeObjects[$type];
			if (!$doc->isAccessible()) continue;
			if (!empty($sql)) $sql .= "\nUNION\n";
			
			// get field names
			$messageIDFieldName = $doc->getIDFieldName();
			$messageIDFieldName = strpos($messageIDFieldName, '.') !== false ? $messageIDFieldName : "messageTable.".$messageIDFieldName;
			
			$sql .= "(	
				SELECT		".$func."(".$messageIDFieldName.") AS messageID,
						'".$type."' AS messageType
				FROM 		".$doc->getTableName()." messageTable
						".$doc->getJoins()."
			)";
		}
		
		// send search query
		$types = array();
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$types[$row['messageType']] = $row['messageID'];
		}
		
		return $types;
	}
	
	/**
	 *
	 */
	public function loadDocuments($type, $min, $max) {
		// get search type object
		$doc = SearchEngine::$searchTypeObjects[$type];
		if (!$doc->isAccessible()) continue;
		
		// get field names
		$messageIDFieldName = $doc->getIDFieldName();
		$messageIDFieldName = strpos($messageIDFieldName, '.') !== false ? $messageIDFieldName : "messageTable.".$messageIDFieldName;
		$subjectFieldNames = $doc->getSubjectFieldNames();
		$messageFieldNames = $doc->getMessageFieldNames();
		$userIDFieldName = $doc->getUserIDFieldName();
		$usernameFieldName = $doc->getUsernameFieldName();
		$timeFieldName = $doc->getTimeFieldName();
		
		$sql = "SELECT		
					'".$type."' AS messageType,
					".$messageIDFieldName." AS messageID,
					CAST(messageTable.".reset($subjectFieldNames)." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS subject,
					CAST(messageTable.".reset($messageFieldNames)." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS message,
					".$userIDFieldName." AS userID,
					CAST(".$usernameFieldName." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS username,
					".$timeFieldName." AS time
			FROM 		".$doc->getTableName()." messageTable
					".$doc->getJoins()."
			WHERE		".$messageIDFieldName." BETWEEN $min AND $max
					".(!empty($conditions[$type]) ? " ".(!empty($q) ? "AND" : "")." (".$conditions[$type].")" : "")."
			GROUP BY	messageID";

		$result = WCF::getDB()->sendQuery($sql, $limit);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$this->addDocument($row);
		}
	}
	
	/**
	 *
	 */
	protected function addDocument($fields) {
		$part = new Apache_Solr_Document();
		foreach ( $fields as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $deppval ) {
					$part->setMultiValue( $key, $deppval );
				}
			}
			else {
				$part->$key = $value;
			}
		}
		
		$this->documents[] = $part;
	}
	
	/**
	 *
	 */
	public function doCrawl($types, $limit) {
		foreach($this->getIndexStatus($types) as $typeName => $status) {
		
			// nothing to do?
			if($row['total'] == $row['current']) {
				continue;
			}
			
			if (!isset(SearchEngine::$searchTypeObjects[$type])) {
				throw new SystemException('unknown search type '.$type, 101001);
			}
			
			$this->loadDocuments($row['current'] + 1, min($row['total'], $row['current'] + 1 + $limit));

			// write to solr
			$this->commit();
		}
	}
	
	/**
	 * 
	 */
	public function getIndexStatus($types = null) {
		
		// read available types
		$status = array();
		
		// get types
		$types = is_array($types) ? $types : SearchEngine::getSearchTypes();
		
		// set counters to zero
		foreach ($types as $type) {
			$status[$type] = array(
				'current' => 0,
				'total' => 0,
				'percent' => 0,
			);
		}
		
		// read current status
		$sql = 'SELECT		typeName,
					c
			FROM (
				SELECT 		typeID, 
						COUNT(typeID) AS c
				FROM		wcf'.WCF_N.'_solr_index 
				GROUP BY 	typeID
			) x
			INNER JOIN 	wcf'.WCF_N.'_searchable_message_type USING(typeID)';
		while ($row = WCF::getDB()->fetchArray($sql)) {
			$typeName = $row['typeName'];
			$status[$typeName]['current'] = $row['c'];
		}

		// read totals
		foreach ($this->getTotals($types, 'COUNT') as $typeName => $count) {
			$percent = $count ? 100 / $count * $status[$typeName]['current'] : 0;
			$status[$typeName]['total'] = $count;
		}
		
		return $status;
	}
}
?>
