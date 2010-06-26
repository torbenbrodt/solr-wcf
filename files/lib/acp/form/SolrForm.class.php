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
	 * Active menu item
	 *
	 * @var	string
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.solr.index';
	
	/**
	 * Permission
	 *
	 * @var	string
	 */
	public $neededPermissions = 'admin.contest.canAddClass';


	public function readParameters() {
		parent::readParameters();
	}
	/**
	 * @see Form::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();

		if (isset($_POST['topic'])) $this->topic = StringUtil::trim($_POST['topic']);
	}

	/**
	 * @see Page::readData()
	 */
	public function readData() {
		parent::readData();

		$this->bridge = new SolrBridge();
		$this->status = $this->bridge->getIndexStatus();
	}

	/**
	 * @see Form::save()
	 */
	public function save() {
		parent::save();

		// save
		$this->contestClass = ContestClassEditor::create($this->topic, $this->text, 
			$this->parentClassID, $this->position, WCF::getLanguage()->getLanguageID());
		$this->saved();

		// reset values
		$this->topic = $this->text = $this->parentClassID = '';
		$this->languageID = $this->position = 0;

		// show success message
		WCF::getTPL()->assign('success', true);
	}

	/**
	 * @see Page::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();

		WCF::getTPL()->assign(array(
			'results' => $this->status,
		));
	}
}
?>
