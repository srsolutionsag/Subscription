<?php
// ini_set('display_errors', 'stdout');
require_once __DIR__ . "/../vendor/autoload.php";

/**
 * GUI-Class msSubscriptionGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 *
 * @ilCtrl_isCalledBy msSubscriptionGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class msSubscriptionGUI {

	const CMD_CLEAR = 'clear';
	const CMD_CONFIRM_DELETE = 'confirmDelete';
	const CMD_DELETE = 'delete';
	const CMD_EDIT = 'edit';
	const CMD_INVITE = 'invite';
	const CMD_KEEP = 'keep';
	const CMD_LNG = 'updateLanguageKey';
	const CMD_LIST_OBJECTS = 'listObjects';
	const CMD_REINVITE = 'reinvite';
	const CMD_REMOVE_UNREGISTERED = 'removeUnregistered';
	const CMD_SEND_FORM = 'sendForm';
	const CMD_SHOW_FORM = 'showForm';
	const CMD_SUBSCRIBE = 'subscribe';
	const CMD_TRIAGE = 'triage';
	const SYSTEM_USER = 6;
	const EMAIL_FIELD = 'sr_ms_email_list_field';
	const MATRICULATION_FIELD = 'sr_ms_matriculation_list_field';
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
	 * @var ilObjUser
	 */
	protected $usr;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	/**
	 * @param $parent
	 */
	function __construct($parent = NULL) {
		global $DIC, $objDefinition;
		/**
		 * @var $objDefinition ilObjectDefinition
		 */
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->ctrl = $DIC->ctrl();
		$this->parent = $parent;
		$this->toolbar = $DIC->toolbar();
		$this->tabs = $DIC->tabs();
		$this->usr = $DIC->user();
		$this->access = $DIC->access();
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
		if (!$this->pl->isActive()) {
			ilUtil::sendFailure('Active Plugin first', true);
			ilUtil::redirect('index.php');
		}

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

		$this->tpl->getStandardTemplate();
		$this->tpl->show();

		return true;
	}


	private function initHeader() {
		global $DIC;
		$list_gui = ilObjectListGUIFactory::_getListGUIByType($this->obj->getType());
		$this->tpl->setTitle($this->obj->getTitle());
		$this->tpl->setDescription($this->obj->getDescription());
		if (ilObject::_lookupType($this->obj->getId()) == 'crs') {
			if ($this->obj->getOfflineStatus()) {
				$this->tpl->setAlertProperties($list_gui->getAlertProperties());
			}
		}
		$this->tpl->setTitleIcon(ilUtil::getTypeIconPath($this->obj->getType(), $this->obj->getId(), 'big'));
		$this->tabs->setBackTarget($this->pl->txt('main_back'), $this->ctrl->getLinkTargetByClass(array(
			ilRepositoryGUI::class,
			'ilObj' . $this->obj_def->getClassName($this->obj->getType()) . 'GUI',
		), 'members'));

		$DIC["ilLocator"]->addRepositoryItems($this->obj_ref_id);
		$this->tpl->setLocator($DIC["ilLocator"]->getHTML());
		$this->tpl->addCss($this->pl->getDirectory() . '/templates/default/Subscription/main.css');
	}


	/**
	 * @return string
	 */
	public function getStandardCommand() {
		return self::CMD_SHOW_FORM;
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case self::CMD_SHOW_FORM:
			case self::CMD_SEND_FORM:
			case self::CMD_LIST_OBJECTS:
			case self::CMD_TRIAGE:
			case self::CMD_REMOVE_UNREGISTERED:
			case self::CMD_CLEAR:
			case self::CMD_LNG:
				if (!$this->access->checkAccess('write', '', $this->obj_ref_id)) {
					ilUtil::sendFailure($this->pl->txt('main_no_access'));
					ilUtil::redirect('index.php');

					return;
				}
				$this->$cmd();
				break;
		}
	}


	public function showForm() {
		$this->initForm();
		ilUtil::sendInfo($this->pl->txt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->tpl->setContent($this->form->getHTML());
	}


	public function initForm() {
		$this->form = new  ilPropertyFormGUI();
		$this->form->setTitle($this->pl->txt('main_form_title_usage_' . msConfig::getUsageType()));
		//		$this->form->setDescription($this->pl->txt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		if (msConfig::getValueByKey('use_email')) {
			$te = new ilTextareaInputGUI($this->pl->txt('main_field_emails_title'), self::EMAIL_FIELD);
			$te->setInfo($this->pl->txt('main_field_emails_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		if (msConfig::getValueByKey('use_matriculation')) {
			$te = new ilTextareaInputGUI($this->pl->txt('main_field_matriculation_title'), self::MATRICULATION_FIELD);
			$te->setInfo($this->pl->txt('main_field_matriculation_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		$this->form->addCommandButton(self::CMD_SEND_FORM, $this->pl->txt('main_send_form'));
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
						$obj->setDeleted(msConfig::getValueByKey(msConfig::F_PURGE));
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
		if (msConfig::getValueByKey(msConfig::ENBL_INV)) {
			ilUtil::sendInfo($this->pl->txt('main_msg_emails_sent_usage_' . msConfig::getUsageType()), true);
		} else {
			ilUtil::sendInfo($this->pl->txt('main_msg_triage_finished'), true);
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
		if (msConfig::getValueByKey(msConfig::F_SYSTEM_USER)) {
			$mail = new ilMail(msConfig::getValueByKey(msConfig::F_SYSTEM_USER));
		} else {
			$mail = new ilMail(self::SYSTEM_USER);
		}

		$sf = array(
			'obj_title' => ilObject2::_lookupTitle(ilObject2::_lookupObjId($msSubscription->getObjRefId())),
			'role' => $this->pl->txt('main_role_' . $msSubscription->getRole()),
			'inv_email' => $msSubscription->getMatchingString(),
			'link' => ILIAS_HTTP_PATH . '/goto.php?target=subscr_' . $msSubscription->getToken(),
			'username' => $this->usr->getFullname(),
			'email' => $this->usr->getEmail(),
		);

		$mail_body = vsprintf($this->pl->txt('main_notification_body'), $sf);
		$mail_body = preg_replace("/\\\\n/um", "\n", $mail_body);
		$subject = $reinvite ? $this->pl->txt('main_notification_subject_reinvite') : $this->pl->txt('main_notification_subject');
		$mail->sendMail($msSubscription->getMatchingString(), '', '', $subject, $mail_body, false, array( 'normal' ));
	}


	protected function removeUnregistered() {
		$where = array(
			'obj_ref_id' => $_GET['obj_ref_id'],
			'deleted' => false,
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

		ilUtil::sendInfo($this->pl->txt("remove_unregistered_info"), true);

		if (msSubscription::where($where)->count() > 0) {
			$this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
		} else {
			$this->ctrl->redirect($this, self::CMD_SHOW_FORM);
		}
	}


	protected function clear() {
		$where = array(
			'obj_ref_id' => $_GET['obj_ref_id'],
			'deleted' => false,
		);

		/**
		 * @var $msSubscription msSubscription
		 */
		foreach (msSubscription::where($where)->get() as $msSubscription) {
			$msSubscription->setDeleted(true);
			$msSubscription->update();
		}

		ilUtil::sendInfo($this->pl->txt("clear_info"), true);

		$this->ctrl->redirect($this, self::CMD_SHOW_FORM);
	}


	/**
	 * @return \ilObjCourse|\ilObjGroup
	 */
	public function getObj() {
		return $this->obj;
	}


	public function updateLanguageKey() {
		global $DIC;
		/**
		 * @var $ilLog ilLog
		 */
		$ilLog = $DIC["ilLog"];

		$ilLog->write('updateLanguageKey');
		$ilLog->write(print_r($_POST, true));
		exit;
	}
}
