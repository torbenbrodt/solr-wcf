<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchEngine.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');


/**
 * solr class abstraction
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
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

	protected static $typeids = null;

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
		#$this->solr->addDocuments( $this->documents );
		#$this->solr->commit();

		// mark items as done
		$sql = "INSERT IGNORE INTO
				wcf".WCF_N."_solr_index
				(typeID, messageID)
			VALUES ";
		foreach($this->documents as $doc) {
			$typeID = $this->getTypeID($doc->messageType);
			$sql .= "($typeID,".$doc->messageID."),";
		}
		$sql = rtrim($sql, ',');
		$result = WCF::getDB()->sendQuery($sql);

		// reset array
		$this->documents = array();

		// optimize solr index
		#$this->solr->optimize();
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

	private function cleanText($message) {
		require_once(WCF_DIR.'lib/data/message/bbcode/MessageParser.class.php');

		// add cache resources
		WCF::getCache()->addResource('bbcodes', WCF_DIR.'cache/cache.bbcodes.php', WCF_DIR.'lib/system/cache/CacheBuilderBBCodes.class.php');
		WCF::getCache()->addResource('smileys', WCF_DIR.'cache/cache.smileys.php', WCF_DIR.'lib/system/cache/CacheBuilderSmileys.class.php');

		$parser = MessageParser::getInstance();
		$parser->setOutputType('text/plain');
		$message = StringUtil::stripHTML($message);
		return $parser->parse($message, false, false, true, false);
	}

	/**
	 *
	 * @return	integer	number of added documents
	 */
	public function loadDocuments($type, $min, $max, $limit) {
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
			GROUP BY	messageID
			ORDER BY	messageID ASC";

		$result = WCF::getDB()->sendQuery($sql, $limit);
		$i = 0;
		while ($row = WCF::getDB()->fetchArray($result)) {
			$row['message'] = $this->cleanText($row['message']);
			$this->addDocument($row);
			$i++;
		}
		return $i;
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
	public function doCrawl($types = null, $limit = null) {

		// get types
		$types = is_array($types) ? $types : $this->getSearchTypes();

		$i = 0;

		foreach($this->getIndexStatus($types, 'MAX') as $type => $status) {

			// nothing to do?
			if($status['total'] == $status['current']) {
				continue;
			}
		// get search type object
		$doc = SearchEngine::$searchTypeObjects[$type];
		if (!$doc->isAccessible()) continue;

			if (!isset(SearchEngine::$searchTypeObjects[$type])) {
				throw new SystemException('unknown search type '.$type, 101001);
			}
			$j = $this->loadDocuments($type, $status['current'] + 1, $status['total'], $limit);
			if($j) {
				$i += $j;

				// write to solr
				$this->commit();
			}
		}

		return $i;
	}

	private function getTypeID($type) {

		if(self::$typeids === null) {
			self::$typeids = array();

			$sql = 'SELECT		*
				FROM 		wcf'.WCF_N.'_searchable_message_type';
			$result = WCF::getDB()->sendQuery($sql);
			while ($row = WCF::getDB()->fetchArray($result)) {
				self::$typeids[$row['typeName']] = $row['typeID'];
			}
		}
		return self::$typeids[$type];
	}
	
	private function getSearchTypes() {
		$types = SearchEngine::getSearchTypes();
		$return = array();
		foreach($types as $type) {
			$doc = SearchEngine::$searchTypeObjects[$type];
			if (!$doc->isAccessible()) continue;
			
			$return[] = $type;
		}
		return $return;
	}

	/**
	 *
	 */
	public function getIndexStatus($types = null, $func = 'COUNT') {

		// read available types
		$status = array();

		// get types
		$types = is_array($types) ? $types : $this->getSearchTypes();

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
						'.$func.'(messageID) AS c
				FROM		wcf'.WCF_N.'_solr_index
				GROUP BY 	typeID
			) x
			INNER JOIN 	wcf'.WCF_N.'_searchable_message_type USING(typeID)
			WHERE 		typeName IN ("'.implode('","', $types).'")';
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$typeName = $row['typeName'];
			$status[$typeName]['current'] = $row['c'];
		}

		// read totals
		foreach ($this->getTotals($types, $func) as $typeName => $count) {
			$status[$typeName]['total'] = $count;
			$status[$typeName]['percent'] = $count ? 100 / $count * $status[$typeName]['current'] : 0;
		}

		return $status;
	}
}
?>
