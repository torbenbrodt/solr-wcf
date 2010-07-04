<?php
require_once(WCF_DIR.'lib/data/message/util/SearchResultTextParser.class.php');

/**
 * This class extends the viewable contest entry by functions for a search result output.
 *
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrSearchResult extends DatabaseObject {

	public function isViewable() {
		return true;
	}

	/**
	 * @see ViewableContest::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		require_once(WCF_DIR.'lib/data/message/bbcode/MessageParser.class.php');
		MessageParser::getInstance()->setOutputType('text/html');
		$message = MessageParser::getInstance()->parse($this->message, $enableSmilies = true, $enableHtml = true, $enableBBCodes = true);
		
		return SearchResultTextParser::parse($message);
	}
}
?>
