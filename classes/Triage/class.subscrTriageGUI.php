<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('./Services/Object/classes/class.ilObject2.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.subscr.php');

/**
 * Class subscrTriageGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy subscrTriageGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class subscrTriageGUI {

	/**
	 * @var ilSubscriptionPlugin
	 */
	protected $pl;
	/**
	 * @var msSubscription
	 */
	protected $subscription;


	public function __construct() {
		global $ilDB, $ilUser, $ilCtrl, $tpl;
		/**
		 * @var $ilDB   ilDB
		 * @var $ilUser ilObjUser
		 * @var $ilCtrl ilCtrl
		 * @var $tpl    ilTemplate
		 */
		$this->db = $ilDB;
		$this->tpl = $tpl;
		$this->user = $ilUser;
		$this->ctrl = $ilCtrl;
		$this->pl = ilSubscriptionPlugin::getInstance();

		$this->token = $_REQUEST['token'];
		$this->subscription = msSubscription::getInstanceByToken($this->token);
		$this->ctrl->setParameter($this, 'token', $this->token);
	}


	public function executeCommand() {
		$cmd = $this->ctrl->getCmd('start');
		if (!$this->subscription instanceof msSubscription) {
			throw new ilException('This token has already been used');
		}
		$this->{$cmd}();

		$this->tpl->getStandardTemplate();
		$this->tpl->show();
	}


	protected function hasLogin() {
		$this->redirectToLogin();
	}


	protected function hasNoLogin() {
		$this->determineLogin();
	}


	public function start() {
		if (msConfig::getValueByKey('ask_for_login')) {
			if ($this->subscription->getAccountType() == msAccountType::TYPE_SHIBBOLETH) {
				ilUtil::sendInfo('Ihre E-Mailadresse wurde als SwitchAAI-Adresse erkannt. Sie können sich direkt einloggen. Klicken Sie auf Login und wählen Sie Ihre Home-Organisation aus.');
				$this->tpl->setContent('<a href="' . $this->getLoginLonk()
				                       . '" class="submit">Login</a>');
			} else {
				$this->showLoginDecision();
			}
		} else {
			$this->determineLogin();
		}

		return;
	}


	protected function showLoginDecision() {
		$this->tpl->getStandardTemplate();
		$this->tpl->setVariable('BASE', msConfig::getPath());
		$this->tpl->setTitle($this->pl->txt('triage_title'));

		$de = new ilConfirmationGUI();
		$de->setFormAction($this->ctrl->getFormAction($this));

		$str = $this->subscription->getMatchingString() . ', Ziel: '
		       . ilObject2::_lookupTitle(ilObject2::_lookupObjId($this->subscription->getObjRefId()));
		$de->addItem('token', $this->token, $str);

		$de->setHeaderText($this->pl->txt('qst_already_account'));
		$de->setConfirm($this->pl->txt('main_yes'), 'hasLogin');
		$de->setCancel($this->pl->txt('main_no'), 'hasNoLogin');

		$this->tpl->setContent($de->getHTML());
	}


	public function determineLogin() {
		if (msConfig::checkShibboleth() AND $this->subscription->getAccountType()
		                                    == msAccountType::TYPE_SHIBBOLETH
		) {
			$this->redirectToLogin();
		} else {
			if (msConfig::getValueByKey('allow_registration')) {
				$this->redirectToTokenRegistrationGUI();
			} else {
				$this->redirectToLogin();
			}
		}

		return;
	}


	public function redirectToLogin() {
		$this->setSubscriptionToDeleted();
		$link = $this->getLoginLonk();

		ilUtil::redirect($link);
	}


	/**
	 * @return object
	 */
	protected function getRegistrationCode() {
		/**
		 * @var $crs ilObjCourse
		 */
		$crs = ilObjectFactory::getInstanceByRefId($this->subscription->getObjRefId());
		if (!$crs->isRegistrationAccessCodeEnabled()) {
			$crs->enableRegistrationAccessCode(1);
			$crs->update();
		}

		return $crs->getRegistrationAccessCode();
	}


	protected function setSubscriptionToDeleted() {
		$this->subscription->setDeleted(true);
		$this->subscription->update();
	}


	protected function redirectToTokenRegistrationGUI() {
		$this->ctrl->setParameterByClass('ilTokenRegistrationGUI', 'token', $this->token);
		$this->ctrl->redirectByClass(array( 'ilUIPluginRouterGUI', 'ilTokenRegistrationGUI' ));
	}


	/**
	 * @return string
	 */
	protected function getLoginLonk() {
		return msConfig::getPath() . 'goto.php?target' . $this->subscription->getContextAsString()
		       . '_' . $this->subscription->getObjRefId() . '_rcode' . $this->getRegistrationCode();
	}
}
