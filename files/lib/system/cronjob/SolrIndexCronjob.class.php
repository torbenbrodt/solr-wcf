<?php
require_once(WCF_DIR.'lib/data/cronjobs/Cronjob.class.php');
require_once(WCF_DIR.'lib/data/solr/SolrBridge.php');

/**
 * pushs everything to solr index
 * 
 * @author	Torben Brodt
 * @package	de.easy-coding.wcf.solr
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 */
class SolrIndexCronjob implements Cronjob {
	
	/**
	 * @see Cronjob::execute()
	 */
	public function execute($data) {
		$bridge = new SolrBridge();
		$bridge->doIndex(null, 100);
	}
}
?>
