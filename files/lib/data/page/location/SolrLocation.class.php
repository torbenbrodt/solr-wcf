<?php
// wcf imports
require_once(WCF_DIR.'lib/data/page/location/Location.class.php');
require_once(WCF_DIR.'lib/data/solr/Solr.class.php');

/**
 * SolrLocation is an implementation of Location for the user solr page.
 *
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrLocation implements Location {
	/**
	 * list of solr entry ids
	 * 
	 * @var	array<integer>
	 */
	public $cachedEntryIDArray = array();
	
	/**
	 * list of solr entries
	 * 
	 * @var	array<Solr>
	 */
	public $entries = null;
	
	/**
	 * @see Location::cache()
	 */
	public function cache($location, $requestURI, $requestMethod, $match) {
		#$this->cachedEntryIDArray[] = $match[1]; TODO SolrLocation
	}
	
	/**
	 * @see Location::get()
	 */
	public function get($location, $requestURI, $requestMethod, $match) {
		if ($this->entries == null) {
			$this->readEntries();
		}
		
		$solrID = $match[1];
		if (!isset($this->entries[$solrID])) {
			return '';
		}
		
		return WCF::getLanguage()->get($location['locationName'], array(
			'$entry' => '<a href="index.php?page=Solr&amp;solrID='.$solrID.SID_ARG_2ND.'">'.StringUtil::encodeHTML($this->entries[$solrID]->subject).'</a>'
		));
	}
	
	/**
	 * Gets entries.
	 */
	protected function readEntries() {
		$this->entries = array();
		
		if (!count($this->cachedEntryIDArray)) {
			return;
		}
		
		$sql = "SELECT		solr.*
			FROM		wcf".WCF_N."_solr solr
			WHERE		solr.solrID IN (".implode(',', $this->cachedEntryIDArray).")";
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$this->entries[$row['solrID']] = new Solr(null, $row);
		}
	}
}
?>
