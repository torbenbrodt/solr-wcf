<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

// tagging imports
require_once(WCF_DIR.'lib/data/tag/Tag.class.php');
require_once(WCF_DIR.'lib/data/tag/TagEngine.class.php');
require_once(WCF_DIR.'lib/data/tag/TagCloudWrapper.class.php');

// reloaded
require_once(WCF_DIR.'lib/util/TaggingReloadedUtil.class.php');

/**
 * Displays the tags under search query
 *
 * @author	Torben Brodt
 * @package	de.easy-coding.wcf.taggingreloaded.solr
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-3.0.html>
 */
class TaggingReloadedSolrPageListener implements EventListener {
	// base
	protected $eventObj;
	protected $className;

	// data
	protected $tags = array();

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		$this->eventObj = $eventObj;
		$this->className = $className;
		
		if(method_exists($this, $eventName)) {
			$this->$eventName();
		}
	}
	
	/**
	 * gets tags similar to the current tag
	 */
	protected function querySimilarTagsquerySimilarTags($tagIDs, $limit = 25) {
		$sql = "SELECT		tag.*,
					counter
			FROM (
				SELECT
					b.tagID,
					SUM(b.weight) AS counter
				FROM (
					SELECT		objectID
					FROM		wcf".WCF_N."_tag_to_object
					WHERE		tagID IN (".implode(",", ArrayUtil::toIntegerArray($tagIDs)).")
					ORDER BY	objectID DESC
					LIMIT		".intval($limit)."
				) a
				INNER JOIN 	wcf".WCF_N."_tagging3 b USING(objectID)
				GROUP BY 	b.tagID
				ORDER BY	counter DESC
				LIMIT           ".intval($limit)."
			) x
			INNER JOIN	wcf".WCF_N."_tag tag USING(tagID)";
		return $sql;
	}
	
	protected function fromQuery($sql) {
		$result = WCF::getDB()->sendQuery($sql);
		$tags = array();
		while ($row = WCF::getDB()->fetchArray($result)) {
			$tags[$row['name']] = new Tag(null, $row);
		}
		
		// optional beautify
		$wrapper = new TagCloudWrapper($tags);
		return $wrapper->getTags();
	}
	
	/**
         * @see Page::readData()
         */
	protected function readData () {
		$quoted_names = $tagIDs = array();

		$query = TaggingReloadedUtil::fromMagicString($this->eventObj->query);
		foreach($query as $tag) {
			$quoted_names[] = escapeString($tag->name);
		}
		
		if(count($quoted_names)) {
			$sql = "SELECT		tagID
				FROM 		wcf".WCF_N."_tag tag
				WHERE		name IN ('".implode("','", $quoted_names)."')";
			$result = WCF::getDB()->sendQuery($sql);
			$tags = array();
			while ($row = WCF::getDB()->fetchArray($result)) {
				$tagIDs[] = $row['tagID'];
			}
		}

		if(count($tagIDs)) {
			$this->tags = $this->fromQuery($this->querySimilarTagsquerySimilarTags($tagIDs));
		}
	}

	/**
         * @see Page::assignVariables()
         */
	protected function assignVariables () {
		if(count($this->tags)) {
			WCF::getTPL()->append('specialStyles', '<link rel="stylesheet" type="text/css" href="'.RELATIVE_WCF_DIR.'style/taggingreloaded.css" />');
			WCF::getTPL()->assign('tags', $this->tags);
			
			WCF::getTPL()->append('additionalContentFooterElements', '<br style="clear:both"/><br/><div class="border content">
				<div class="container-1">
					<h3 class="subHeadline">'.WCF::getLanguage()->get('wcf.taggingreloaded.similar').'</h3>
					'.WCF::getTPL()->fetch('tagCloud').'
				</div>
			</div>');
		}
	}
}
?>
