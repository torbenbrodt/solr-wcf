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
	public $fq = array();
	public $facets = array();
	public $facet_field = array(
		'site',
		'type',
		'author',
	);

	protected $total = 0;
	
	/**
	 * Creates a new SearchResultPage object. without searchid
	 */
	public function __construct() {
		parent::__construct(0);
	}

	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();

		if (isset($_REQUEST['fq'])) $this->fq = $_REQUEST['fq'];
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
			$args = $_GET;
			unset($args['pageNo']);
			$url = 'index.php?'.http_build_query($args);
			HeaderUtil::redirect($url);
			exit;
		}
		
		if (isset($_POST['q'])) {
			$url = 'index.php?page=SolrSearch&q='.$_POST['q'];
			HeaderUtil::redirect($url);
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
		$tmp = $solr->search($this->query, $offset, $this->itemsPerPage, array(
			'fq' => $this->fq,
			'facet' => 'true',
			'facet.field' => $this->facet_field
		));
		
		if(!$tmp || !$tmp->highlighting) {
			return;
		}

		$this->total = intval($tmp->response->numFound);

		$facets = isset($tmp->facet_counts->facet_fields) ? get_object_vars($tmp->facet_counts->facet_fields) : array();
                foreach($facets as $key => &$val) {
                        $val = get_object_vars($val);
                        $val = array_filter($val, create_function('$a', 'return $a > 0;'));
                }
                $this->facets = array_filter($facets);

		// transform data in wcf compatible format
		foreach($tmp->highlighting as $id => $row) {
			$data = array();
			
			if(preg_match('/^([a-zA-Z]+):(\d+)$/', $id, $res)) {
				list($messageType, $messageID) = array($res[1], $res[2]);
			} else {
				$messageType = 'solr';
				$messageID = $i;
				$data['url'] = $id;
				$data['displayurl'] = $row->url[0];
				$data['image'] = 'http://images.websnapr.com/?size=s&url='.parse_url($data['url'], PHP_URL_HOST);
			}

			$data['messageID'] = $messageID;
			$data['type'] = $data['messageType'] = $messageType;
			
			// press first dimension of stdobject into clean array
			$data['message'] = isset($row->content[0]) ? $this->convertSingleWhitespace($row->content[0]) : '';
			$data['subject'] = isset($row->title[0]) ? $row->title[0] : '';
			$data['username'] = isset($row->autor[0]) ? $row->autor[0] : '';
			if(isset($row->tstamp[0]) && preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $row->tstamp[0], $match)) {
				$data['time'] = mktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1]);
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

	/**
	 * @see Page::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();

		WCF::getTPL()->assign(array(
			'facets' => $this->facets,
			'singleColumn' => empty($this->facets),
			'allowSpidersToIndexThisPage' => true
		));
	 }
}
?>
