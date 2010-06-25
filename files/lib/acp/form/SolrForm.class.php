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
	public $activeMenuItem = 'wcf.acp.menu.link.contest.class';
	
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
	 * @see Form::validate()
	 */
	public function validate() {
		parent::validate();
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
			'topic' => $this->topic,
			'text' => $this->text,
			'contestClass' => $this->contestClass,
			'languageID' => $this->languageID,
			'class' => $this->contestClass,
			'action' => 'add'
		));
	}
}
?>
