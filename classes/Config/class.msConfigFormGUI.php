<?php


require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.msConfig.php');

/**
 * GUI-Class msConfigFormGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 *
 */
class msConfigFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilSubscriptionConfigGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilSubscriptionPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, 'clip_ext_id');
		$this->setFormAction($this->ctrl->getFormAction($parent_gui));
		$this->initForm();
	}


	private function initForm() {
		$this->setTitle($this->pl->txt('admin_' . 'conf_title'));
		$this->setDescription($this->pl->txt('admin_' . 'conf_description'));

		$cb_mail = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_USE_EMAIL), msConfig::F_USE_EMAIL);
		{
			$cb_enable_invitation = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::ENBL_INV), msConfig::ENBL_INV);
			{
				$cb_reg = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_ALLOW_REGISTRATION), msConfig::F_ALLOW_REGISTRATION);
				$ask_for_login = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_ASK_FOR_LOGIN), msConfig::F_ASK_FOR_LOGIN);
				$cb_reg->addSubItem($ask_for_login);

				$fixed_email = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_FIXED_EMAIL), msConfig::F_FIXED_EMAIL);
				$cb_reg->addSubItem($fixed_email);
				$cb_enable_invitation->addSubItem($cb_reg);
				$this->addItem($cb_mail);

				$cb_shib = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_SHIBBOLETH), msConfig::F_SHIBBOLETH);
				{
					$metadata_xml = new ilTextInputGUI($this->pl->txt('admin_' . msConfig::F_METADATA_XML), msConfig::F_METADATA_XML);
					$cb_shib->addSubItem($metadata_xml);
				}

				$cb_enable_invitation->addSubItem($cb_shib);
			}



			$cb_mail->addSubItem($cb_enable_invitation);

			$cb_send_mails = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_SEND_MAILS), msConfig::F_SEND_MAILS);
//			$cb_mail->addSubItem($cb_send_mails);

		}

		$use_matriculation = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_USE_MATRICULATION), msConfig::F_USE_MATRICULATION);
		$this->addItem($use_matriculation);

		$show_names = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_SHOW_NAMES), msConfig::F_SHOW_NAMES);
		$this->addItem($show_names);

        $activate_groups = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_ACTIVATE_GROUPS), msConfig::F_ACTIVATE_GROUPS);
        $this->addItem($activate_groups);

		$system_user = new ilTextInputGUI($this->pl->txt('admin_' . msConfig::F_SYSTEM_USER), msConfig::F_SYSTEM_USER);
		$this->addItem($system_user);

		$cb_purge = new ilCheckboxInputGUI($this->pl->txt('admin_' . msConfig::F_PURGE), msConfig::F_PURGE);
		$this->addItem($cb_purge);

        $ignore_subtree = new ilTextInputGUI($this->pl->txt('admin_' . msConfig::F_IGNORE_SUBTREE), msConfig::F_IGNORE_SUBTREE);
        $ignore_subtree->setInfo($this->pl->txt('admin_' . msConfig::F_IGNORE_SUBTREE . '_info'));
        $this->addItem($ignore_subtree);

		$this->addCommandButtons();
	}


	public function fillForm() {
		$array = array();
		foreach ($this->getItems() as $item) {
			$array = $this->fillValue($item, $array);
		}

		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			$this->writeValue($item);
		}
        ilUtil::sendSuccess($this->pl->txt('admin_save_succeed'), true);
		return true;
	}


	protected function addCommandButtons() {
		$this->addCommandButton('save', $this->pl->txt('admin_' . 'form_button_save'));
		$this->addCommandButton('cancel', $this->pl->txt('admin_' . 'form_button_cancel'));
	}


	/**
	 * @param $item
	 * @param $array
	 *
	 * @return mixed
	 */
	protected function fillValue($item, $array) {
		if (get_class($item) != 'ilFormSectionHeaderGUI') {
			$key = $item->getPostVar();
			$array[$key] = msConfig::get($key);
			foreach ($item->getSubItems() as $sub_item) {
				$array = $this->fillValue($sub_item, $array);
			}
		}

		return $array;
	}


	/**
	 * @param $item
	 */
	protected function writeValue($item) {
		if (get_class($item) != 'ilFormSectionHeaderGUI') {
			/**
			 * @var $item ilCheckboxInputGUI
			 */
			$key = $item->getPostVar();
			msConfig::set($key, $this->getInput($key));
			foreach ($item->getSubItems() as $subitem) {
				$this->writeValue($subitem);
			}
		}
	}
}