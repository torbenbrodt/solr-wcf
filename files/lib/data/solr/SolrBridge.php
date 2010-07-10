<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/SearchEngine.class.php');
require_once(WCF_DIR.'lib/data/solr/SafeSearchableMessageType.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');


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
		$this->solr->addDocuments( $this->documents );
		$this->solr->commit();

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

		// reset array
		$this->documents = array();

		// optimize solr index
		$this->solr->optimize();
	}

	/**
	 *
	 */
	protected function getTotals(array $types, $func) {

		$sql = '';
		foreach ($types as $type) {

			// get search type object
			$doc = $this->getSearchType($type);
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
		$doc = $this->getSearchType($type);
		if (!$doc->isAccessible()) continue;

		// get field names
		$messageIDFieldName = $doc->getIDFieldName();
		$messageIDFieldName = strpos($messageIDFieldName, '.') !== false ? $messageIDFieldName : "messageTable.".$messageIDFieldName;
		$subjectFieldNames = $doc->getSubjectFieldNames();
		$messageFieldNames = $doc->getMessageFieldNames();
		$userIDFieldName = $doc->getUserIDFieldName();
		$usernameFieldName = $doc->getUsernameFieldName();
		$timeFieldName = $doc->getTimeFieldName();
		
		$select = array();
		$select[] = "'".$type."' AS messageType";
		$select[] = $messageIDFieldName." AS messageID";

		$subjects = 0;
		if($subjectFieldNames) {
			foreach($subjectFieldNames as $column) {
				$select[] = "CAST(messageTable.".$column." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS subject".(++$subjects);
			}
		}
		
		$messages = 0;
		if($messageFieldNames) {
			foreach($messageFieldNames as $column) {
				$select[] = "CAST(messageTable.".$column." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS message".(++$messages);
			}
		}
		
		if($userIDFieldName) {
			$select[] = $userIDFieldName." AS userID";
		}
		
		if($usernameFieldName) {
			$select[] = "CAST(".$usernameFieldName." AS CHAR CHARACTER SET ".WCF::getDB()->getCharset().") AS username";
		}
		
		if($timeFieldName) {
			$select[] = $timeFieldName." AS time";
		}

		$sql = "SELECT
					".implode(",", $select)."
			FROM 		".$doc->getTableName()." messageTable
					".$doc->getJoins()."
			WHERE		".$messageIDFieldName." BETWEEN $min AND $max
			GROUP BY	messageID
			ORDER BY	messageID ASC";

		$result = WCF::getDB()->sendQuery($sql, $limit);
		$i = 0;
		while ($row = WCF::getDB()->fetchArray($result)) {
			$row['subject'] = '';
			for($j=0; $j<$subjects; $j++) {
				$row['subject'] += $row['subject'.$i].' ';
				unset($row['subject'.$i]);
			}
			$row['subject'] = rtrim($row['subject']);
			
			$row['message'] = '';
			for($j=0; $j<$messages; $j++) {
				$row['message'] += $row['message'.$i].' ';
				unset($row['message'.$i]);
			}
			$row['message'] = rtrim($row['message']);
		
			$this->addDocument($row);
			$i++;
		}
		return $i;
	}
	
	/**
	 * there are many ways to work with multiple sources. 
	 * my decision was against the multicore approach. i decided on flattening the schema.xml
	 *
	 * @see http://wiki.apache.org/solr/MultipleIndexes
	 * @deprecated
	 */
	protected function addDocumentSolr(&$doc, $row) {
	
		// very unique ID
		$doc->id = $row['messageType'].$row['messageID'];
		$doc->messageType = $row['messageType'];
		$doc->messageID = $row['messageID'];
		$doc->subject = $this->cleanText($row['subject']);
		$doc->message = $this->cleanText($row['message']);
		$doc->userID = $row['userID'];
		$doc->username = $row['username'];
		
		// Convert date from timestamp into ISO 8601 format.
		// @see http://lucene.apache.org/solr/api/org/apache/solr/schema/DateField.html
		$doc->time = gmdate('Y-m-d\TH:i:s\Z', $row['time']);
		
		// get special tags from message
		$text = $row['message'];
		
		// Extract HTML tag contents from $text and add to boost fields.
		$tags_to_index = array(
			'h1' => 'tags_h1',
			'h2' => 'tags_h2_h3',
			'h3' => 'tags_h2_h3',
			'h4' => 'tags_h4_h5_h6',
			'h5' => 'tags_h4_h5_h6',
			'h6' => 'tags_h4_h5_h6',
			'u' => 'tags_inline',
			'b' => 'tags_inline',
			'i' => 'tags_inline',
			'strong' => 'tags_inline',
			'em' => 'tags_inline',
			'a' => 'tags_a',
		);

		// Strip off all ignored tags.
		$text = strip_tags($text, '<'. implode('><', array_keys($tags_to_index)) .'>');

		preg_match_all('@<('. implode('|', array_keys($tags_to_index)) .')[^>]*>(.*)</\1>@Ui', $text, $matches);
		foreach ($matches[1] as $key => $tag) {
			$document->{$tags_to_index[$tag]} .= ' '. $matches[2][$key];
		}
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
		$doc->content = $this->cleanText($row['message']);
		$doc->title = $this->cleanText($row['subject']);
		$doc->cache = '';
		$doc->tstamp = date('YmdHis', $row['time']);

		// fields for index-anchor plugin
		#$doc->anchor = '';

		// fields for index-more plugin
		#$doc->type = '';
		#$doc->contentLength = '';
		#$doc->lastModified = '';
		#$doc->date = '';

		// fields for index-more plugin
		#$doc->lang = '';

		// fields for subcollection plugin
		#$doc->subcollection = '';

		// fields for feed plugin
		$doc->author = $row['username'];
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
	public function doIndex($types = null, $limit = null) {
	
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
			// get search type object
			$doc = $this->getSearchType($type);
			if (!$doc->isAccessible()) continue;
			
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
		return new SafeSearchableMessageType(SearchEngine::$searchTypeObjects[$type]);
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
