<?php
// wcf imports
require_once(WCF_DIR.'lib/page/SearchResultPage.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');

/**
 * SearchForm handles given search request and shows the extended search form.
 * 
 */
class SolrSearchPage extends SearchResultPage {
	protected $total = 0;
	
	/**
	 * Creates a new SearchResultPage object. without searchid
	 */
	public function __construct() {
		parent::__construct(0);
	}

	/**
	 * removes all duplicate whitespaces
	 *
	 * @param	string		$string
	 * @return	string
	 */
	protected static function convertSingleWhitespace($string) {
		$string = str_replace(array("\t", "&nbsp;", "\r", "\n", "-", urldecode("%C2%A0")), " ", $string);
		while(strpos($string, '  ') !== false) {
			$string = str_replace('  ', ' ', $string);
		}
		return $string;
	}
	
	/**
	 * Gets the data of the selected search from database.
	 */
	protected function readSearch() {
		$solr = new SolrService(SOLR_URL);

		$tmp = $solr->search($this->query, ($this->pageNo - 1) * $this->itemsPerPage, $this->itemsPerPage);
		$this->total = intval($tmp->response->numFound);

		$i = 0;
		foreach($tmp->highlighting as $row) {
					
			//  set solr defaults
			if(empty($row->messageType)) {
				$row->messageType = 'solr';
			}
			if(empty($row->messageID)) {
				$row->messageID = $i;
			}
		
			// increment message key position
			$this->result[$i++] = $row;
		}
	}
	
	/**
	 * Caches the message data.
	 */
	protected function cacheMessageData() {
		parent::cacheMessageData();
		
		$messageCache = array();
		foreach($this->result as $key => $row) {
			if($row['messageType'] == 'solr') {
				$messageCache[$key] = $row;
			}
		}
		
		if(count($messageCache)) {
			$object = SearchEngine::getSearchTypeObject('solr');
			$object->cacheMessageData(null, $messageCache);
		}
	}
	
	/**
	 * @see MultipleLinkPage::countItems()
	 */
	public function countItems() {
		parent::countItems();
		
		return $this->total;
	}
}
?>
