<?php
// WCF include
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * overwrites default searchform
 *
 * @author	Torben Brodt
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrAbstractPageListener implements EventListener {
	protected $eventObj;
	protected $className;

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {	
		$this->eventObj = $eventObj;
		$this->className = $className;

		switch ($eventName) {
			case 'assignVariables':
				$this->assignVariables();
				break;
		}
	}
	
	/**
	 * @see UserPage::assignVariables()
	 */
	protected function assignVariables() {
		/*
		 * $searchScript=search script; default=index.php?form=search
		 * $searchFieldName=name of the search input field; default=q
		 * $searchFieldValue=default value of the search input field; default=content of $query
		 * $searchFieldTitle=title of search input field; default=language variable wbb.header.search.query
		 * $searchFieldOptions=special search options for popup menu; default=empty
		 * $searchExtendedLink=link to extended search form; default=index.php?form=search{@SID_ARG_2ND}
		 * $searchHiddenFields=optional hidden fields; default=empty
		 * $searchShowExtendedLink=set to false to disable extended search link; default=true
		 */
		 WCF::getTPL()->assign(array(
		 	'searchScript' => 'index.php?page=SolrSearch'.SID_ARG_2ND,
		 	'searchFieldName' => 'q'
		 ));
	}
}
?>
