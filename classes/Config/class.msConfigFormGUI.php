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


	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	protected function txt($key) {
		return $this->pl->txt('admin_' . $key);
	}


	private function initForm() {
		$this->setTitle($this->txt('conf_title'));
		$this->setDescription($this->txt('conf_description'));

		$activate_courses = new ilCheckboxInputGUI($this->txt(msConfig::F_ACTIVATE_COURSES), msConfig::F_ACTIVATE_COURSES);
		$this->addItem($activate_courses);

		$activate_groups = new ilCheckboxInputGUI($this->txt(msConfig::F_ACTIVATE_GROUPS), msConfig::F_ACTIVATE_GROUPS);
		$this->addItem($activate_groups);

		$ti = new ilFormSectionHeaderGUI();
		$ti->setTitle($this->txt('header_input'));
		$this->addItem($ti);

		$cb_mail = new ilCheckboxInputGUI($this->txt(msConfig::F_USE_EMAIL), msConfig::F_USE_EMAIL);
		{
			$cb_enable_invitation = new ilCheckboxInputGUI($this->txt(msConfig::ENBL_INV), msConfig::ENBL_INV);
			{
				$cb_reg = new ilCheckboxInputGUI($this->txt(msConfig::F_ALLOW_REGISTRATION), msConfig::F_ALLOW_REGISTRATION);
				$ask_for_login = new ilCheckboxInputGUI($this->txt(msConfig::F_ASK_FOR_LOGIN), msConfig::F_ASK_FOR_LOGIN);
				$cb_reg->addSubItem($ask_for_login);

				$fixed_email = new ilCheckboxInputGUI($this->txt(msConfig::F_FIXED_EMAIL), msConfig::F_FIXED_EMAIL);
				$cb_reg->addSubItem($fixed_email);
				$cb_enable_invitation->addSubItem($cb_reg);
				$this->addItem($cb_mail);

				$cb_shib = new ilCheckboxInputGUI($this->txt(msConfig::F_SHIBBOLETH), msConfig::F_SHIBBOLETH);
				{
					$metadata_xml = new ilTextInputGUI($this->txt(msConfig::F_METADATA_XML), msConfig::F_METADATA_XML);
					$cb_shib->addSubItem($metadata_xml);
				}

				$cb_enable_invitation->addSubItem($cb_shib);
			}

			$cb_mail->addSubItem($cb_enable_invitation);
		}

		$use_matriculation = new ilCheckboxInputGUI($this->txt(msConfig::F_USE_MATRICULATION), msConfig::F_USE_MATRICULATION);
		$this->addItem($use_matriculation);

		$ti = new ilFormSectionHeaderGUI();
		$ti->setTitle($this->txt('header_general'));
		$this->addItem($ti);

		$show_names = new ilCheckboxInputGUI($this->txt(msConfig::F_SHOW_NAMES), msConfig::F_SHOW_NAMES);
		$this->addItem($show_names);

		$system_user = new ilTextInputGUI($this->txt(msConfig::F_SYSTEM_USER), msConfig::F_SYSTEM_USER);
		$this->addItem($system_user);

		$cb_purge = new ilCheckboxInputGUI($this->txt(msConfig::F_PURGE), msConfig::F_PURGE);
		$this->addItem($cb_purge);

		$activate_ignore_subtree = new ilCheckboxInputGUI($this->txt(msConfig::F_IGNORE_SUBTREE_ACTIVE), msConfig::F_IGNORE_SUBTREE_ACTIVE);
		{
			$ignore_subtree = new ilTextInputGUI($this->txt(msConfig::F_IGNORE_SUBTREE), msConfig::F_IGNORE_SUBTREE);
			$ignore_subtree->setInfo($this->txt(msConfig::F_IGNORE_SUBTREE . '_info'));
			$activate_ignore_subtree->addSubItem($ignore_subtree);
		}
		$this->addItem($activate_ignore_subtree);

		$cb_send_mails = new ilCheckboxInputGUI($this->txt(msConfig::F_SEND_MAILS), msConfig::F_SEND_MAILS);
		$this->addItem($cb_send_mails);

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
		if (!$this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			$this->writeValue($item);
		}
		ilUtil::sendSuccess($this->pl->txt('admin_save_succeed'), true);

		return true;
	}


	protected function addCommandButtons() {
		$this->addCommandButton(ilSubscriptionConfigGUI::CMD_SAVE, $this->txt('form_button_save'));
		$this->addCommandButton(ilSubscriptionConfigGUI::CMD_CANCEL, $this->txt('form_button_cancel'));
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
			$array[$key] = msConfig::getValue($key);
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