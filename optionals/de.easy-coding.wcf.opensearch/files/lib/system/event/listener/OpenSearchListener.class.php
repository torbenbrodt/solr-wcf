<?php
//import wcf event listener
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * adds opensearch meta tags
 *
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.opensearch
 */
class OpenSearchListener implements EventListener {
	private static $called = false;

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if(!self::$called) {
			self::$called = true;

			WCF::getTPL()->append(array(
				'specialStyles' => WCF::getTPL()->fetch('openSearchHead')
			));
		}
	}
}
?>
