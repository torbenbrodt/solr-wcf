<?php

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
		SearchEngne::getSearchTypes();
		
		$path = parse_url(SOLR_URL);
		$this->solr = new SolrService($path['host'], isset($path['port']) ? $path['port'] : 80, $path['path']);
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
					(typeName, messageID)";
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
	protected function getTotals(array $types) {

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
				SELECT		MAX(".$messageIDFieldName.") AS messageID,
						'".$type."' AS messageType
				FROM 		".$doc->getTableName()." messageTable
						".$doc->getJoins()."
				WHERE		1
						".(!empty($conditions[$type]) ? " ".(!empty($q) ? "AND" : "")." (".$conditions[$type].")" : "")."
				GROUP BY	messageID
			)";
		}
		
		// send search query
		$types = array();
		$result = WCF::getDB()->sendQuery($sql, $limit);
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
			);
		}
		
		// read current status
		
		while ($row = WCF::getDB()->fetchArray($result)) {
			$typeName = $row['typeName'];
			$status[$typeName]['current'] = $row['messageID'];
		}

		// read totals
		foreach ($this->getTotals($type) as $typeName => $total) {
			$status[$typeName]['total'] = $row['messageID'];
		}
		
		return $messages;
	}
}
?>
