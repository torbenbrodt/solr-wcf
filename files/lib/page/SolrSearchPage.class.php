<?php
// wcf imports
require_once(WCF_DIR.'lib/page/SearchResultPage.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrService.php');

/**
 * SearchForm handles given search request and shows the extended search form.
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrSearchPage extends SearchResultPage {
	public $templateName = 'solr';
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
	protected function convertSingleWhitespace($string) {
		$string = str_replace(array("\t", "&nbsp;", "\r", "\n", "-", urldecode("%C2%A0")), " ", $string);
		while(strpos($string, '  ') !== false) {
			$string = str_replace('  ', ' ', $string);
		}
		return $string;
	}

	/**
	 * if the client is (or claims to be) connected via HTTPS
	 * @return boolean
	 */
	protected function isHTTPS() {
		return isset($_SERVER["HTTP_X_PROTO"]) ||
			(isset($_SERVER['HTTPS']) &&
				!empty($_SERVER['HTTPS']) &&
				$_SERVER['HTTPS'] !== 'off');
	}
	
	/**
	 * Gets the data of the selected search from database.
	 */
	protected function readSearch() {
	
		// seo friendly redirect of page 1
		if(isset($_GET['pageNo']) && $_GET['pageNo'] == 1) {
			$url = ($this->isHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].str_replace("&pageNo=1", "", $_SERVER['REQUEST_URI']);
			HeaderUtil::redirect($url, false, true);
			exit;
		}
		
		if (isset($_POST['q'])) {
			$url = ($this->isHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/index.php?page=SolrSearch&q='.$_POST['q'];
			HeaderUtil::redirect($url, false, true);
			exit;
		}
		
		// get search query
		if (isset($_REQUEST['q'])) $this->query = $_REQUEST['q'];
	
		if(!$this->query) {
			return;
		}
		
		// param
		$offset = (max($this->pageNo,1) - 1) * $this->itemsPerPage;
		$i = $offset;

		// query search
		$solr = new SolrService();
		$tmp = $solr->search($this->query, $offset, $this->itemsPerPage);
		$this->total = intval($tmp->response->numFound);
		
		if(!$tmp || !$tmp->highlighting) {
			return;
		}
		
		// transform data in wcf compatible format
		foreach($tmp->highlighting as $url => $row) {
			// press first dimension of stdobject into clean array
			$data = array();
			$data['messageID'] = $i;
			$data['message'] = $this->convertSingleWhitespace($row->content[0]);
			$data['subject'] = $row->title[0];
			$data['url'] = $url;
			$data['displayurl'] = $row->url[0];
			$data['image'] = 'http://image.browsershots.de/'.parse_url($data['url'], PHP_URL_HOST);
			$data['time'] = time(); // TODO: time
			$data['messageType'] = 'solr';
			$data['type'] = 'solr';
		
			//  set solr defaults
			if(!empty($row->messageType)) {
				$data['messageType'] = $row->messageType;
			}
			if(!empty($row->type)) {
				$data['type'] = $row->type;
			}
			if(!empty($row->messageID)) {
				$data['messageID'] = $row->messageID;
			}
	
			// increment message key position
			$this->result[$i++] = $data;
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
