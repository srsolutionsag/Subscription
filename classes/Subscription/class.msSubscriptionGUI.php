<?php
// ini_set('display_errors', 'stdout');

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/AccountType/class.msAccountType.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/UserStatus/class.msUserStatus.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.msSubscription.php');
require_once('class.msSubscriptionTableGUI.php');
require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
require_once('./Services/Component/classes/class.ilComponent.php');
@include_once('./Services/Link/classes/class.ilLink.php');
require_once('./Services/Mail/classes/class.ilMail.php');
require_once('./Services/Object/classes/class.ilObject2.php');

/**
 * GUI-Class msSubscriptionGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 *
 * @ilCtrl_isCalledBy msSubscriptionGUI: ilRouterGUI
 */
class msSubscriptionGUI {

	const CMD_DELETE = 'delete';
	const CMD_KEEP = 'keep';
	const CMD_SUBSCRIBE = 'subscribe';
	const CMD_INVITE = 'invite';
	const CMD_REINVITE = 'reinvite';
	const CMD_LNG = 'updateLanguageKey';
	const SYSTEM_USER = 6;
	const EMAIL_FIELD = 'sr_ms_email_list_field';
	const MATRICULATION_FIELD = 'sr_ms_matriculation_list_field';
	const CMD_LIST_OBJECTS = 'listObjects';
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilObjectDefinition
	 */
	protected $obj_def;
	/**
	 * @var int
	 */
	protected $obj_ref_id = 0;
	/**
	 * @var msSubscriptionTableGUI
	 */
	protected $table;
	/**
	 * @var ilObjCourse|ilObjGroup
	 */
	protected $obj;


	/**
	 * @param $parent
	 */
	function __construct($parent = NULL) {
		global $tpl, $ilCtrl, $ilToolbar, $ilTabs, $objDefinition;
		/**
		 * @var $tpl           ilTemplate
		 * @var $ilCtrl        ilCtrl
		 * @var $ilToolbar     ilToolbarGUI
		 * @var $ilTabs        ilTabsGUI
		 * @var $objDefinition ilObjectDefinition
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->obj_def = $objDefinition;
		$this->pl = ilSubscriptionPlugin::getInstance();
		//		$this->pl->updateLanguageFiles();

		$this->obj_ref_id = $_GET['obj_ref_id'];
		$this->obj = ilObjectFactory::getInstanceByRefId($this->obj_ref_id);
		$class_name = $this->obj_def->getClassName($this->obj->getType());
		$this->ctrl->setParameterByClass('ilObj' . $class_name . 'GUI', 'ref_id', $this->obj_ref_id);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$this->initHeader();
		$this->ctrl->saveParameter($this, 'obj_ref_id');
		$this->ctrl->setContext($this->obj->getId(), $this->obj->getType());
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		if ($_GET['rl'] == 'true') {
			$this->pl->updateLanguages();
		}
		switch ($cmd) {
			default:
				$this->performCommand($cmd);
				break;
		}

		return true;
	}


	private function initHeader() {
		global $ilLocator;
		/**
		 * @var $ilLocator ilLocatorGUI
		 */
		$list_gui = ilObjectListGUIFactory::_getListGUIByType($this->obj->getType());
		$this->tpl->setTitle($this->obj->getTitle());
		$this->tpl->setDescription($this->obj->getDescription());
		if (ilObject::_lookupType($this->obj->getId()) == 'crs') {
			if ($this->obj->getOfflineStatus()) {
				$this->tpl->setAlertProperties($list_gui->getAlertProperties());
			}
		}
		$this->tpl->setTitleIcon(ilUtil::getTypeIconPath($this->obj->getType(), $this->obj->getId(), 'big'));
		$this->tabs->setBackTarget($this->pl->getDynamicTxt('main_back'), $this->ctrl->getLinkTargetByClass(array(
			'ilRepositoryGUI',
			'ilObj' . $this->obj_def->getClassName($this->obj->getType()) . 'GUI'
		), 'members'));
		$ilLocator->addRepositoryItems($this->obj_ref_id);
		$this->tpl->setLocator($ilLocator->getHTML());
		$this->tpl->addCss('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/templates/default/Subscription/main.css');
	}


	/**
	 * @return string
	 */
	public function getStandardCommand() {
		return 'showForm';
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		global $ilAccess;
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		switch ($cmd) {
			case 'showForm':
			case 'sendForm':
			case self::CMD_LIST_OBJECTS:
			case 'triage':
			case 'clear':
			case self::CMD_LNG:
				if (!$ilAccess->checkAccess('write', '', $this->obj_ref_id)) {
					ilUtil::sendFailure($this->pl->getDynamicTxt('main_no_access'));
					ilUtil::redirect('index.php');

					return;
				}
				$this->$cmd();
				break;
		}
	}


	public function showForm() {
		$this->initForm();
		ilUtil::sendInfo($this->pl->getDynamicTxt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->tpl->setContent($this->form->getHTML());
	}


	public function initForm() {
		$this->form = new  ilPropertyFormGUI();
		$this->form->setTitle($this->pl->getDynamicTxt('main_form_title_usage_' . msConfig::getUsageType()));
		//		$this->form->setDescription($this->pl->getDynamicTxt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		if (msConfig::get('use_email')) {
			$te = new ilTextareaInputGUI($this->pl->getDynamicTxt('main_field_emails_title'), self::EMAIL_FIELD);
			$te->setInfo($this->pl->getDynamicTxt('main_field_emails_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		if (msConfig::get('use_matriculation')) {
			$te = new ilTextareaInputGUI($this->pl->getDynamicTxt('main_field_matriculation_title'), self::MATRICULATION_FIELD);
			$te->setInfo($this->pl->getDynamicTxt('main_field_matriculation_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		$this->form->addCommandButton('sendForm', $this->pl->getDynamicTxt('main_send_form'));
	}


	public function sendForm() {
		$contextObjType = $this->obj->getType();
		switch ($contextObjType) {
			case 'grp':
				$context = msSubscription::CONTEXT_GRP;
				break;
			case 'crs':
				$context = msSubscription::CONTEXT_CRS;
				break;
		}
		foreach (msSubscription::seperateEmailString($_POST[self::EMAIL_FIELD]) as $mail) {
			msSubscription::insertNewRequests($this->obj_ref_id, $mail, msSubscription::TYPE_EMAIL, $context);
		}
		foreach (msSubscription::seperateMatriculationString($_POST[self::MATRICULATION_FIELD]) as $matriculation) {
			msSubscription::insertNewRequests($this->obj_ref_id, $matriculation, msSubscription::TYPE_MATRICULATION, $context);
		}
		$this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
	}


	private function initTable() {
		$this->table = new msSubscriptionTableGUI($this, self::CMD_LIST_OBJECTS);
	}


	public function listObjects() {
		$this->initTable();
		$this->tpl->setContent($this->table->getHTML());
	}


	public function cancel() {
		$this->ctrl->redirect($this);
	}


	public function triage() {
		foreach ($_POST as $k => $v) {
			if (preg_match("/obj_([0-9]*)/um", $k, $m)) {
				/**
				 * @var $obj msSubscription
				 */
				$obj = msSubscription::find($m[1]);
				$obj->setRole($v['role']);
				switch ($v['cmd']) {
					case self::CMD_DELETE:
						$obj->setDeleted(true);
						break;
					case '':
					case self::CMD_KEEP:
						$obj->setDeleted(msConfig::get(msConfig::F_PURGE));
						break;
					case self::CMD_INVITE:
					case self::CMD_REINVITE:
						$this->sendMail($obj);
						$obj->setInvitationsSent(true);
						break;
					case self::CMD_SUBSCRIBE:
						$obj->assignToObject();
						break;
				}
				$obj->update();
			}
		}
		if (msConfig::get(msConfig::ENBL_INV)) {
			ilUtil::sendInfo($this->pl->getDynamicTxt('main_msg_emails_sent'), true);
		} else {
			ilUtil::sendInfo($this->pl->getDynamicTxt('main_msg_triage_finished'), true);
		}
		$this->listObjects();
		$this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
	}


	/**
	 * @return string
	 */
	public function getCourseLink() {

		$link = ilLink::_getLink($this->obj->getRefId()) . '&cmd=members';

		return $link;
	}


	/**
	 * @param msSubscription $msSubscription
	 * @param bool           $reinvite
	 */
	public function sendMail(msSubscription $msSubscription, $reinvite = false) {
		global $ilUser;
		/**
		 * @var $ilUser ilObjUser
		 */
		if (msConfig::get(msConfig::F_SYSTEM_USER)) {
			$mail = new ilMail(msConfig::get(msConfig::F_SYSTEM_USER));
		} else {
			$mail = new ilMail(self::SYSTEM_USER);
		}

		$sf = array(
			'obj_title' => ilObject2::_lookupTitle(ilObject2::_lookupObjId($msSubscription->getObjRefId())),
			'role' => $this->pl->getDynamicTxt('main_role_' . $msSubscription->getRole()),
			'inv_email' => $msSubscription->getMatchingString(),
			'link' => ILIAS_HTTP_PATH . '/goto.php?target=subscr_' . $msSubscription->getToken(),
			'username' => $ilUser->getFullname(),
			'email' => $ilUser->getEmail(),
		);


		$mail_body = vsprintf($this->pl->getDynamicTxt('main_notification_body'), $sf);
		$mail_body = preg_replace("/\\\\n/um", "\n", $mail_body);
		$subject = $reinvite ? $this->pl->getDynamicTxt('main_notification_subject_reinvite') : $this->pl->getDynamicTxt('main_notification_subject');
		$mail->sendMail($msSubscription->getMatchingString(), '', '', $subject, $mail_body, false, array( 'normal' ));
	}


	protected function clear() {
		$where = array(
			'obj_ref_id' => $_GET['obj_ref_id'],
			'deleted' => false
		);
		/**
		 * @var $msSubscription msSubscription
		 */
		foreach (msSubscription::where($where)->get() as $msSubscription) {
			if ($msSubscription->isDeletable()) {
				$msSubscription->setDeleted(true);
				$msSubscription->update();
			}
		}
		$this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
	}


	/**
	 * @return \ilObjCourse|\ilObjGroup
	 */
	public function getObj() {
		return $this->obj;
	}


	public function updateLanguageKey() {
		global $ilLog;
		/**
		 * @var $ilLog ilLog
		 */
		$ilLog->write('updateLanguageKey');
		$ilLog->write(print_r($_POST, true));
		exit;
	}
}

?>