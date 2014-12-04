<?php
//error_reporting(E_ALL ^ E_STRICT ^E_NOTICE);
//ini_set('display_errors', 'stdout');
require_once('./Services/Registration/classes/class.ilAccountRegistrationGUI.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
@include_once('./classes/class.ilLink.php');
@include_once('./Services/Link/classes/class.ilLink.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/AccountType/class.msAccountType.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/UserStatus/class.msUserStatus.php');
require_once('./Services/Init/classes/class.ilStartUpGUI.php');

/**
 * Class ilTokenRegistrationGUI
 *
 * @ilCtrl_isCalledBy ilTokenRegistrationGUI: ilAccountRegistrationGUI
 * @ilCtrl_isCalledBy ilTokenRegistrationGUI: ilRouterGUI
 */
class ilTokenRegistrationGUI extends ilAccountRegistrationGUI {

	/**
	 * @var msSubscription
	 */
	protected $subscription;


	public function __construct() {
		/**
		 * @var $ilCtrl ilCtrl
		 */
//		$_POST['username'] = 'anonymous';
//		$_POST['password'] = 'anonymous';
		ilInitialisation::initILIAS();
		global $ilCtrl;

		parent::__construct();
		$this->pl = ilSubscriptionPlugin::getInstance();
		$this->ctrl = $ilCtrl;
		$this->token = $_GET['token'];
		$this->subscription = msSubscription::where(array( 'token' => $this->token ))->first();
		$this->ctrl->saveParameter($this, 'token');
	}


	public function executeCommand() {
		global $tpl;
		$cmd = $this->ctrl->getCmd();
		//		var_dump($cmd); // FSX
		//		exit;

		if ($cmd) {
			$this->$cmd();
		} else {

			$this->displayForm();
		}

		//$tpl->show();

		return true;

	}


	protected function __initForm($a_force_code = false) {
		parent::__initForm();
		/**
		 * @var $form ilPropertyFormGUI
		 * @var $usr_email ilTextInputGUI
		 */
		$this->form->setFormAction($this->ctrl->getFormActionByClass(array('ilRouterGUI', 'ilTokenRegistrationGUI')));

		$username = $this->form->getItemByPostVar('username');
		$username->setValue($this->subscription->getMatchingString());

		$usr_email = $this->form->getItemByPostVar('usr_email');
		$matriculation = $this->form->getItemByPostVar('usr_matriculation');

		switch ($this->subscription->getSubscriptionType()) {
			case msSubscription::TYPE_EMAIL:
				$usr_email->setDisabled(msConfig::get('fixed_email'));
				$usr_email->setValue($this->subscription->getMatchingString());
				$retype = in_array('setRetypeValue', get_class_methods(get_class($usr_email)));
				if ($retype) {
					$usr_email->setRetypeValue($this->subscription->getMatchingString());
				}
				if(msConfig::get('fixed_email')) {
//					$usr_email->setPostVar('usr_email_fixed');
					$hidden = new ilHiddenInputGUI('usr_email');
					$hidden->setValue($this->subscription->getMatchingString());
					$this->form->addItem($hidden);
					if($retype) {
						$hidden_retype = new ilHiddenInputGUI('usr_email_retype');
						$hidden_retype->setValue($this->subscription->getMatchingString());
						$this->form->addItem($hidden_retype);
					}
				}
				break;
			case msSubscription::TYPE_MATRICULATION:
				$matriculation->setDisabled(msConfig::get('fixed_email'));
				$matriculation->setValue($this->subscription->getMatchingString());
		}
	}


	public function displayForm() {
		if (! $this->subscription OR $this->subscription->getDeleted() == 1) {
			$this->tpl->getStandardTemplate();
			$this->tpl->setContent($this->pl->txt('main_not_invalid_token'));
		} elseif ($this->subscription->getUserStatus() == msUserStatus::STATUS_USER_CAN_BE_ASSIGNED OR
			$this->subscription->getUserStatus() == msUserStatus::STATUS_ALREADY_ASSIGNED
		) {
			$this->assignUser();
			$this->redirectToCourse();
		} else {
			parent::displayForm();
		}
	}


	public function assignUser() {
		$obj_id = ilObject::_lookupObjId($this->subscription->getCrsRefId());
		$participants = new ilCourseParticipants($obj_id);
		$participants->add($this->subscription->user_status_object->getUsrId(), $this->subscription->getRole());
		$this->subscription->setDeleted(true);
		$this->subscription->update();
	}


	public function redirectToCourse() {
		header('Location: ' . ilLink::_getStaticLink($this->subscription->getCrsRefId()));
	}


	public function saveForm() {
//		echo '<pre>' . print_r($_POST, 1) . '</pre>';
		if (parent::saveForm()) {
			//$this->login();


			//$this->form->getInput('');



			$this->assignUser();
			$_POST['username'] = 'root';
			$_POST['password'] = 'Kohifu78';
			ilInitialisation::initILIAS();
			$this->redirectToCourse();
		}
	}


	public function subscribeToAllCourses() {
	}


	/**
	 * @param $a_email
	 *
	 * @return string
	 */
	private static function getUsernameByEmail($a_email) {
		$before_at = explode('@', $a_email);
		$first_last = explode('.', $before_at[0]);
		if (count($first_last) != 2) {
			$name = $before_at[0];
		} else {
			$name = substr($first_last[0], 0, 1) . '.' . $first_last[1];
		}
		while (strlen($name) < 3) {
			$name .= chr(rand(48, 57));
		}

		return $name;
	}
}