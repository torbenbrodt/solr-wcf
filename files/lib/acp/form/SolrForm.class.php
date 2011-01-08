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
	}

	/**
	 * @see Page::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();

		WCF::getTPL()->assign(array(
			'results' => $this->status,
		));
		
		if($this->errorFieldMessage != '') {
			WCF::getTPL()->assign(array(
				'errorFieldMessage' => $this->errorFieldMessage,
			));
		}
	}
}
?>
