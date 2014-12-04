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
	 * @var int
	 */
	protected $crs_ref_id = 0;
	/**
	 * @var msSubscriptionTableGUI
	 */
	protected $table;
	/**
	 * @var ilObjCourse
	 */
	protected $crs;


	/**
	 * @param $parent
	 */
	function __construct($parent = NULL) {
		global $tpl, $ilCtrl, $ilToolbar, $ilTabs;
		/**
		 * @var $tpl       ilTemplate
		 * @var $ilCtrl    ilCtrl
		 * @var $ilToolbar ilToolbarGUI
		 * @var $ilTabs    ilTabsGUI
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->pl = ilSubscriptionPlugin::getInstance();
//		$this->pl->updateLanguageFiles();

		$this->crs_ref_id = $_GET['crs_ref_id'];
		$this->ctrl->setParameterByClass('ilObjCourseGUI', 'ref_id', $this->crs_ref_id);
		$this->crs = ilObjectFactory::getInstanceByRefId($this->crs_ref_id);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$this->initHeader();
		$this->ctrl->saveParameter($this, 'crs_ref_id');
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
		$list_gui = ilObjectListGUIFactory::_getListGUIByType($this->crs->getType());
		$this->tpl->setTitle($this->crs->getTitle());
		$this->tpl->setDescription($this->crs->getDescription());
		if ($this->crs->getOfflineStatus()) {
			$this->tpl->setAlertProperties($list_gui->getAlertProperties());
		}
		$this->tpl->setTitleIcon(ilUtil::getTypeIconPath('crs', $this->crs->getId(), 'big'));
		$this->tabs->setBackTarget($this->pl->txt('main_back'), $this->ctrl->getLinkTargetByClass(array(
			'ilRepositoryGUI',
			'ilObjCourseGUI'
		), 'members'));
		$ilLocator->addRepositoryItems($this->crs_ref_id);
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
			case 'listObjects':
			case 'triage':
			case 'clear':
				if (! $ilAccess->checkAccess('write', '', $this->crs_ref_id)) {
					ilUtil::sendFailure($this->pl->txt('main_no_access'));
					ilUtil::redirect('index.php');

					return;
				}
				$this->$cmd();
				break;
		}
	}


	public function showForm() {
		//		msSubscription::resetDB();
		$this->initForm();
		ilUtil::sendInfo($this->pl->txt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->tpl->setContent($this->form->getHTML());
	}


	public function initForm() {
		$this->form = new  ilPropertyFormGUI();
		$this->form->setTitle($this->pl->txt('main_form_title_usage_' . msConfig::getUsageType()));
		//		$this->form->setDescription($this->pl->txt('main_form_info_usage_' . msConfig::getUsageType()));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		if (msConfig::get('use_email')) {
			$te = new ilTextareaInputGUI($this->pl->txt('main_field_emails_title'), self::EMAIL_FIELD);
			$te->setInfo($this->pl->txt('main_field_emails_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		if (msConfig::get('use_matriculation')) {
			$te = new ilTextareaInputGUI($this->pl->txt('main_field_matriculation_title'), self::MATRICULATION_FIELD);
			$te->setInfo($this->pl->txt('main_field_matriculation_info'));
			$te->setRows(10);
			$te->setCols(100);
			$this->form->addItem($te);
		}
		$this->form->addCommandButton('sendForm', $this->pl->txt('main_send_form'));
	}


	public function sendForm() {
		foreach (msSubscription::seperateEmailString($_POST[self::EMAIL_FIELD]) as $mail) {
			msSubscription::insertNewRequests($this->crs_ref_id, $mail, msSubscription::TYPE_EMAIL);
		}
		foreach (msSubscription::seperateMatriculationString($_POST[self::MATRICULATION_FIELD]) as $matriculation) {
			msSubscription::insertNewRequests($this->crs_ref_id, $matriculation, msSubscription::TYPE_MATRICULATION);
		}
		$this->ctrl->redirect($this, 'listObjects');
	}


	private function initTable() {
		$this->table = new msSubscriptionTableGUI($this, 'listObjects');
	}


	public function listObjects() {
		$this->initTable();
		$this->tpl->setContent($this->table->getHTML());
	}


	public function cancel() {
		$this->ctrl->redirect($this);
	}


	public function triage() {
		//		echo '<pre>' . print_r($_POST, 1) . '</pre>';
		foreach ($_POST as $k => $v) {
			if (preg_match("/obj_([0-9]*)/um", $k, $m)) {
				/**
				 * @var $obj msSubscription
				 */

				$obj = msSubscription::find($m[1]);
				//				echo '<pre>' . print_r($obj, 1) . '</pre>';
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
						$this->assignToCourse($obj);
						$obj->setDeleted(true);
						break;
				}
				$obj->update();
			}
		}
		if (msConfig::get(msConfig::ENBL_INV)) {
			ilUtil::sendInfo($this->pl->txt('main_msg_emails_sent'), true);
		} else {
			ilUtil::sendInfo($this->pl->txt('main_msg_triage_finished'), true);
		}
		$this->listObjects();
		$this->ctrl->redirect($this, 'listObjects');
		//		ilUtil::redirect($this->getCourseLink());
	}


	/**
	 * @return string
	 */
	public function getCourseLink() {
		/*if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999')) {
			$this->ctrl->initBaseClass('ilRepositoryGUI');
			$this->ctrl->setParameterByClass('ilObjCourseGUI', 'ref_id', $this->crs->getRefId());
			$link = $this->ctrl->getLinkTargetByClass(array( 'ilRepositoryGUI', 'ilObjCourseGUI' ), 'members');
			$this->ctrl->initBaseClass('ilRouterGUI');
		} else {
			$this->ctrl->setTargetScript('repository.php');
			$this->ctrl->initBaseClass('ilRepositoryGUI');
			$this->ctrl->setParameterByClass('ilObjCourseGUI', 'ref_id', $this->crs->getRefId());
			$link = $this->ctrl->getLinkTargetByClass(array( 'ilRepositoryGUI', 'ilObjCourseGUI' ), 'members');
			$this->ctrl->initBaseClass('ilRouterGUI');
			$this->ctrl->setTargetScript('ilias.php');
		}*/
		$link = ilLink::_getLink($this->crs->getRefId()) . '&cmd=members';




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
		//		echo '<pre>' . print_r($mail, 1) . '</pre>';
		$sf = array(
			'crs_title' => ilObject2::_lookupTitle(ilObject2::_lookupObjId($msSubscription->getCrsRefId())),
			'role' => $this->pl->txt('main_role_' . $msSubscription->getRole()),
			'inv_email' => $msSubscription->getMatchingString(),
			'link' => ILIAS_HTTP_PATH . '/goto.php?target=subscr_' . $msSubscription->getToken(),
			'username' => $ilUser->getFullname(),
			'email' => $ilUser->getEmail(),
		);

		//'link' => ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/triage.php?token='
		//. $msSubscription->getToken(),

		$mail_body = vsprintf($this->pl->txt('main_notification_body'), $sf);
		$mail_body = preg_replace("/\\\\n/um", "\n", $mail_body);
		$subject = $reinvite ? $this->pl->txt('main_notification_subject_reinvite') : $this->pl->txt('main_notification_subject');
		$mail->sendMail($msSubscription->getMatchingString(), '', '', $subject, $mail_body, false, array( 'normal' ));
	}


	/**
	 * @param msSubscription $msSubscription
	 */
	public function assignToCourse(msSubscription $msSubscription) {
		$obj_id = ilObject::_lookupObjId($msSubscription->getCrsRefId());
		$participants = new ilCourseParticipants($obj_id);
		$participants->add($msSubscription->user_status_object->getUsrId(), $msSubscription->getRole());
	}


	protected function clear() {
		$where = array(
			'crs_ref_id' => $_GET['crs_ref_id'],
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
		$this->ctrl->redirect($this, 'listObjects');
	}
}

?>