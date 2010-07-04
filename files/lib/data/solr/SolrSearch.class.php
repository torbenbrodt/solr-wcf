<?php
// wcf imports
require_once(WCF_DIR.'lib/data/message/search/AbstractSearchableMessageType.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrSearchResult.class.php');

/**
 * An implementation of SearchableMessageType for searching in user contests.
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrSearch extends AbstractSearchableMessageType {
	protected $messageCache = array();
	
	public function isAccessible() {
		return false;
	}
	
	/**
	 * Caches the data of the messages with the given ids.
	 * just a pseudo definition
	 */
	public function cacheMessageData($messageIDs, $additionalData = null) {
		if(is_array($additionalData)) {
			foreach($additionalData as $row) {
				$entry = new SolrSearchResult($row);
				if($entry->isViewable()) {
					$this->messageCache[$row['messageID']] = $row;
					$this->messageCache[$row['messageID']]['message'] = $entry;
				}
			}
		}
	}
	
	/**
	 * @see SearchableMessageType::getMessageData()
	 */
	public function getMessageData($messageID, $additionalData = null) {
		if (isset($this->messageCache[$messageID])) return $this->messageCache[$messageID];
		return null;
	}
	
	/**
	 * Returns the database table name for this search type.
	 * just a pseudo definition
	 */
	public function getTableName() {
		return 'wcf'.WCF_N.'_solr';
	}
	
	/**
	 * Returns the message id field name for this search type.
	 * just a pseudo definition
	 */
	public function getIDFieldName() {
		return 'solrID';
	}
	
	/**
	 * @see SearchableMessageType::getResultTemplateName()
	 */
	public function getResultTemplateName() {
		return 'searchResultSolr';
	}
}
?>
