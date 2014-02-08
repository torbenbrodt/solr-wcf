<?php

/**
 * searchable message typ has no possiblity to return links
 * see bugreport: http://www.woltlab.com/bugtracker/index.php?page=Bug&bugID=1262
 *
 * this wrapper extracts the correct urls
 *
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrTemplateWrapperUtil {

	/**
	 *
	 */
	public static function parse($messages) {
	
		
		if(count($messages)) {
			// dummy, assign cycles
			WCF::getTPL()->fetch('searchResult');
		}	
		foreach($messages as $item) {
			if(isset($item['url'])) {
				continue;
			}
			$type = SearchEngine::getSearchTypeObject($item['type']);
			
			WCF::getTPL()->assign(array(
				'item' => $item
			));

			$template = $type->getResultTemplateName();
			$return = WCF::getTPL()->fetch($template);
			if(preg_match('/<h3>.*<a href="([^"]+)">/', $return, $res)) {
				$item['message']->url = PAGE_URL.'/'.StringUtil::decodeHTML($res[1]);
			}
		}
		return $messages;
	}
}
