<?php
// wcf imports
require_once(WCF_DIR.'lib/page/AbstractFeedPage.class.php');

/**
 * use search suggestions
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr.opensearch
 */
class SolrSearchSuggestionPage extends AbstractFeedPage {
	
	/**
	 * @see Page::show()
	 */
	public function show() {
		parent::show();
		
		// see http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions#Search_Suggestions_Response
		//  ["sea",["sears","search engines","search engine","search","sears.com","seattle times"]]
	}
}
?>
