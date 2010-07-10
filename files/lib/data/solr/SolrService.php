<?php
require_once(WCF_DIR.'lib/data/solr/Service.php');

/**
 * need to overwrite search servlet
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrService extends Apache_Solr_Service {
	public function __construct() {
		$path = parse_url(SOLR_URL);
		parent::__construct($path['host'], isset($path['port']) ? $path['port'] : 80, $path['path']);
	}

	/**
	 * Construct the Full URLs for the three servlets we reference
	 */
	protected function _initUrls() {
		parent::_initUrls();
		$this->_searchUrl = $this->_constructUrl(SOLR_SERVLET_SEARCH);
	}
}
?>
