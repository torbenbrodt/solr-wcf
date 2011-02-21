<?php
// wcf imports
require_once(WCF_DIR.'lib/page/AbstractFeedPage.class.php');

/**
 * display open search xml widget
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.opensearch
 */
class OpenSearchPage extends AbstractFeedPage {
	
	/**
	 * @see Page::show()
	 */
	public function show() {
		parent::show();
		
		// send content
		WCF::getTPL()->display("openSearch", true);
	}
}
?>
