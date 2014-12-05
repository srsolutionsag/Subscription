<?php
require_once('Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('class.ilSubscriptionPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfigFormGUI.php');

/**
 * Example configuration user interface class
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilSubscriptionConfigGUI extends ilPluginConfigGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	public function __construct() {
		global $lng, $ilCtrl, $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->pl = ilSubscriptionPlugin::getInstance();
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case 'configure':
			case 'save':
			case 'reloadLanguageFiles':
				$this->$cmd();
				break;
		}
	}


	public function configure() {
		$form = new msConfigFormGUI($this);
		$form->fillForm();
		$this->tpl->setContent($form->getHTML());
	}


	protected function save() {
		$form = new msConfigFormGUI($this);
		$form->setValuesByPost();
		if ($form->saveObject()) {
			$this->ctrl->redirect($this, 'configure');
		}
		$this->tpl->setContent($form->getHTML());
	}


	protected function addReloadButton() {
		if (is_writable('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/lang/')) {
			global $ilToolbar;
			/**
			 * @var $ilToolbar ilToolbarGUI
			 */
			$ilToolbar->addButton($this->pl->getDynamicTxt('button_reload_lang_files'), $this->ctrl->getLinkTarget($this, 'reloadLanguageFiles'));
		}
	}
}

?>
