<?php
require_once(BASEDIR.'libs/solr/Service.php');

/**
 * need to overwrite search servlet
 */
class SolrService extends Apache_Solr_Service {
	public function __construct($url) {
		$path = parse_url($url);
		
		parent::__construct($path['host'], isset($path['port']) ? $path['port'] : 80, $path['path']);
	}

	/**
	 * Construct the Full URLs for the three servlets we reference
	 */
	protected function _initUrls() {
		parent::_initUrls();
		$this->_searchUrl = $this->_constructUrl('');
	}
}
?>
