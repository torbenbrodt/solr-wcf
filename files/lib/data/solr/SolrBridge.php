<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchEngine.class.php');
require_once(WCF_DIR.'lib/data/solr/SafeSearchableMessageType.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');
require_once(WCF_DIR.'lib/data/solr/SolrWCF.class.php');


/**
 * solr class abstraction
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrBridge {

	/**
	 * documents to be pushed
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
	 * segment has date format of YmdHis
	 '
	 * @var string
	 */
	protected $segment = '';

	/**
	 * key is typeName, value is typeID
	 *
	 * @var array
	 */
	protected static $typeids = null;

	/**
	 * creates a new instance
	 */
	public function __construct() {

		// load search type objects
		SearchEngine::getSearchTypes();

		$this->solr = new SolrService();
	}

	/**
	 * pushs all documents to solr
	 */
	protected function commit() {
		if($this->documents) {
			$this->solr->addDocuments( $this->documents );

			// mark items as done
			$sql = "INSERT IGNORE INTO
					wcf".WCF_N."_solr_index
					(typeID, messageID)
				VALUES ";
			foreach($this->documents as $doc) {
				list($messageType, $messageID) = explode(":", $doc->id);
				$typeID = $this->getTypeID($messageType);
				$sql .= "($typeID,".$messageID."),";
			}
			$sql = rtrim($sql, ',');
			$result = WCF::getDB()->sendQuery($sql);
		}
		
		// commit
		$this->solr->commit();

		// reset array
		$this->documents = array();

		// optimize solr index
		$this->solr->optimize();
	}

	/**
	 * attention, this function does not care for condition array, since these may depend a custom usersession
	 */
	protected function getTotals(array $types, $func) {

		$sql = '';
		foreach ($types as $type) {

			// get search type object
			$doc = $this->getSearchType($type);

			SolrWCF::startAnonymousStandaloneSession($type);

			if (!$doc->isAccessible()) continue;
			if (!empty($sql)) $sql .= "\nUNION\n";

			// get field names
			$messageIDFieldName = $doc->getIDFieldName();
			$messageIDFieldName = strpos($messageIDFieldName, '.') !== false ? $messageIDFieldName : "messageTable.".$messageIDFieldName;

			$conditions = $doc->getConditions();
			$conditions = empty($conditions) ? 1 : $conditions;

			$sql .= "(
				SELECT		".$func."(messageID) AS messageID,
						'".$type."' AS messageType
				FROM (
					SELECT		$messageIDFieldName AS messageID
					FROM 		".$doc->getTableName()." messageTable
							".$doc->getJoins()."
					WHERE		(".$conditions.")
					GROUP BY	messageID
				) x
			)";
		}
		SolrWCF::endAnonymousStandaloneSession();

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
	protected function loadDocuments($type, $min, $max, $limit) {
		// get search type object
		$doc = $this->getSearchType($type);

		SolrWCF::startAnonymousStandaloneSession($type);

		if (!$doc->isAccessible()) return 0;

		// get field names
		$messageIDFieldName = $doc->getIDFieldName();
		$messageIDFieldName = strpos($messageIDFieldName, '.') !== false ? $messageIDFieldName : "messageTable.".$messageIDFieldName;
		$subjectFieldNames = $doc->getSubjectFieldNames();
		$messageFieldNames = $doc->getMessageFieldNames();
		$userIDFieldName = $doc->getUserIDFieldName();
		$usernameFieldName = $doc->getUsernameFieldName();
		$timeFieldName = $doc->getTimeFieldName();

		$select = $outerselect = array();
		$outerselect[] = '*';
		$select[] = "'".$type."' AS messageType";
		$select[] = $messageIDFieldName." AS messageID";

		$subjects = 0;
		if($subjectFieldNames) {
			foreach($subjectFieldNames as $column) {
				$select[] = "CAST(messageTable.".$column." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS subject".($subjects++);
			}
		}

		$messages = 0;
		if($messageFieldNames) {
			foreach($messageFieldNames as $column) {
				$select[] = "CAST(messageTable.".$column." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS message".($messages++);
			}
		}

		if($userIDFieldName) {
			$select[] = $userIDFieldName." AS userID";
		}
		
		// username field is not allowed to be empty and does not end with string userID
		$hasUsername = !empty($usernameFieldName) && !(
			($x = mb_strrpos($usernameFieldName, 'userID')) !== false && 
			$x == mb_strlen($usernameFieldName) - mb_strlen('userID')
		);
		
		$additionalJoins = '';
		if($userIDFieldName && !$hasUsername) {
			$select[] = "CAST(solr_user.username AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS username";
			$additionalJoins .= ' LEFT JOIN wcf'.WCF_N.'_user solr_user USING(userID) ';
		} else if($usernameFieldName) {
			$select[] = "CAST(".$usernameFieldName." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS username";
		}

		if($timeFieldName) {
			$select[] = $timeFieldName." AS time";
		}

		if($additional = $doc->getAdditionalInnerSelects()) {
			$select[] = $additional;
		}

		if($additional = $doc->getAdditionalOuterSelects()) {
			$outerselect[] = $additional;
		}

		$conditions = $doc->getConditions();
		$conditions = empty($conditions) ? 1 : $conditions;

		$sql = "SELECT		".implode(",", $outerselect)."
			FROM (
				SELECT
						".implode(",", $select)."
				FROM 		".$doc->getTableName()." messageTable
						".$doc->getJoins()."
						".$additionalJoins."
				WHERE		".$messageIDFieldName." BETWEEN $min AND $max
				AND		(".$conditions.")
				GROUP BY	messageID
				ORDER BY	messageID ASC
				LIMIT		".intval($limit)."
			) messageTable
			".$doc->getOuterJoins()."
			GROUP BY	messageID
			ORDER BY	messageID ASC";
		$result = WCF::getDB()->sendQuery($sql);
		$i = 0;

		while ($row = WCF::getDB()->fetchArray($result)) {

			$row['subject'] = '';
			for($j=0; $j<$subjects; $j++) {
				$row['subject'] .= $row['subject'.$j].' ';
				unset($row['subject'.$j]);
			}
			$row['subject'] = rtrim($row['subject']);

			$row['message'] = '';
			for($j=0; $j<$messages; $j++) {
				$row['message'] .= $row['message'.$j].' ';
				unset($row['message'.$j]);
			}
			$row['message'] = rtrim($row['message']);

			$this->addDocument($row);
			$i++;
		}

		SolrWCF::endAnonymousStandaloneSession();
		return $i;
	}

	/**
	 * add a document to queue
	 */
	protected function addDocument($row) {

		$doc = new Apache_Solr_Document();

		// very unique ID
		$doc->id = $row['messageType'].':'.$row['messageID'];

		// core fields
		$doc->segment = $this->segment;
		$doc->digest = md5($doc->id);
		$doc->boost = 1.0;

        	// fields for index-basic plugin
		$doc->host = parse_url(PAGE_URL, PHP_URL_HOST);
		$doc->site = $doc->host;
		$doc->url = PAGE_URL.'/index.php?page=SolrSearch&id='.$doc->id;
		if(isset($row['message'])) $doc->content = $this->cleanText($row['message']);
		if(isset($row['subject'])) $doc->title = $this->cleanText($row['subject']);
		$doc->cache = '';
		if(isset($row['time'])) $doc->tstamp = date('YmdHis', $row['time']);

		// fields for index-anchor plugin
		if(isset($row['anchor'])) $doc->anchor = $row['anchor'];

		// fields for index-more plugin
		$doc->type = $row['messageType'];
		#$doc->contentLength = '';
		#$doc->lastModified = '';
		#$doc->date = '';

		// fields for index-more plugin
		#$doc->lang = '';

		// fields for subcollection plugin
		#$doc->subcollection = '';

		// fields for feed plugin
		if(isset($row['username'])) $doc->author = $row['username'];
		#$doc->tag = '';
		#$doc->feed = '';
		#$doc->publishedDate = '';
		#$doc->updatedDate = '';

		// what we have no place for
		#$doc->userID = $row['userID'];

		$this->documents[] = $doc;
	}

	/**
	 *
	 */
	public function doIndex($types = null, $limit = 50) {

		// set current segments
		$this->segment = date('YmdHis');

		// get types
		$types = is_array($types) ? $types : $this->getSearchTypes();
		$i = 0;

		foreach($this->getIndexStatus($types, 'MAX') as $type => $status) {

			// nothing to do?
			if($status['total'] == $status['current']) {
				continue;
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

	/**
	 *
	 */
	public function doReindex($types = null, $limit = 50) {

		// set current segments
		$this->segment = date('YmdHis');

		// get types
		$types = is_array($types) ? $types : $this->getSearchTypes();
		$reverse = array();
		foreach($types as $type) {
			$reverse[self::getTypeID($type)] = $type;
		}
		$i = 0;

		$sql = 'SELECT		*
			FROM 		wcf'.WCF_N.'_solr_reindex
			WHERE		status = 1
			AND		typeID IN ('.implode(',', array_keys($reverse)).')
			LIMIT		'.$limit;
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {

			$messageType = $reverse[$row['typeID']];
			$j = $this->loadDocuments($messageType, $row['messageID'], $row['messageID'], 1);

			// mark items as done
			$sql = "UPDATE		wcf".WCF_N."_solr_reindex
				SET		status = 0
				WHERE		typeID = ".$row['typeID']."
				AND		messageID = ".$row['messageID'];
			WCF::getDB()->sendQuery($sql);

			// delete
			if(!$j) {
				$j = 1;

				// delete from solr
				$this->solr->deleteById($messageType.':'.$row['messageID']);

				// delete from main index
				$sql = "DELETE FROM	wcf".WCF_N."_solr_index
					WHERE		typeID = ".$row['typeID']."
					AND		messageID = ".$row['messageID'];
				WCF::getDB()->sendQuery($sql);
			}
			$i += $j;
		}

		if($i > 0) {
			$this->commit();
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
			$doc = $this->getSearchType($type);
			if (!$doc->isAccessible()) continue;

			$return[] = $type;
		}
		return $return;
	}

	private function getSearchType($type) {
		if (!isset(SearchEngine::$searchTypeObjects[$type])) {
			throw new SystemException('unknown search type '.$type, 101001);
		}
		return new SafeSearchableMessageType($type, SearchEngine::$searchTypeObjects[$type]);
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
