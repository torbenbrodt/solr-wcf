<?php
// wcf imports
require_once(WCF_DIR.'lib/data/solr/SolrBridge.php');
require_once(WCF_DIR.'lib/acp/form/ACPForm.class.php');

/**
 * solr form
 *
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.solr
 */
class SolrForm extends ACPForm {
	/**
	 * Template name
	 *
	 * @var	string
	 */
	public $templateName = 'solr';

	/**
	 *
	 * @var	string
	 */
	public $errorFieldMessage = '';
	
	/**
	 *
	 */
	protected $status = array();
	
	/**
	 * Active menu item
	 *
	 * @var	string
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.solr.index';


	public function readParameters() {
		parent::readParameters();
	}
	/**
	 * @see Form::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();

		if (isset($_POST['type'])) $this->type = StringUtil::trim($_POST['type']);
	}

	/**
	 * @see Page::readData()
	 */
	public function readData() {
		parent::readData();

		try {
			$this->bridge = new SolrBridge();
			$this->status = $this->bridge->getIndexStatus();
		} catch(Exception $e) {
			$this->errorField = $e->getMessage();
			$this->errorFieldMessage = ': '.$this->errorField;
		}
		
		
		$this->reindex = array();
		$sql = 'SELECT	typeName,
				x.*
			FROM ( 
				SELECT		typeID,
						SUM(IF(status=0,1,0)) AS current,
						COUNT(status) AS total,
						100 / COUNT(status) * SUM(IF(status=0,1,0)) AS percent
				FROM 		wcf'.WCF_N.'_solr_reindex
				GROUP BY	typeID
			) x
			INNER JOIN	wcf'.WCF_N.'_searchable_message_type USING(typeID)';
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			$this->reindex[$row['typeName']] = $row;
		}
	}

	/**
	 * @see Page::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();

		WCF::getTPL()->assign(array(
			'results' => $this->status,
			'reindex' => $this->reindex,
		));
		
		if($this->errorFieldMessage != '') {
			WCF::getTPL()->assign(array(
				'errorFieldMessage' => $this->errorFieldMessage,
			));
		}
	}
}
?>
