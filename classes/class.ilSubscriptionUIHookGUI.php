<?php
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.subscr.php');

/**
 * Class ilSubscriptionUIHookGUI
 *
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilSubscriptionUIHookGUI extends ilUIHookPluginGUI {

	/**
	 * @var array
	 */
	protected static $ignored_subtree = array();
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var ilTree
	 */
	protected $three;


	public function __construct() {
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->access = $DIC->access();
		$this->tree =$DIC->repositoryTree();
		$this->pl = ilSubscriptionPlugin::getInstance();
	}


	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 */
	public function modifyGUI($a_comp, $a_part, $a_par = array()) {
		global $DIC;

		$locations = array(
			array( 'ilobjgroupgui', 'members' ),
			array( 'ilobjcoursegui', 'members' ),
			array( 'ilcourseparticipantsgroupsgui', 'show' ),
			array( 'ilobjcoursegui', 'membersGallery' ),
			array( 'ilobjcoursegui', 'mailMembers' ),
			array( 'ilobjgroupgui', 'membersGallery' ),
			array( 'ilobjgroupgui', 'mailMembers' ),
			array( 'ilsessionoverviewgui', 'listSessions' ),
			array( 'iluimasssubscriptiongui', 'members' ),
			array( 'iluimasssubscriptiongui', 'membersGallery' ),
			array( 'ilcourseeditparticipantstablegui', '*' ),
			array( 'ilsubscriptiongui', '*' ),
			array( 'ilobjcoursegui', 'editMember' ),
			array( 'ilobjcoursegui', 'updateMembers' ),
			array( 'ilobjcoursegui', 'deleteMembers' ),
			array( 'ilobjcoursegui', 'removeMembers' ),
			array( 'ilobjgroupgui', 'editMember' ),
			array( 'ilobjgroupgui', 'updateMembers' ),
			array( 'ilobjgroupgui', 'confirmDeleteMembers' ),
			array( 'ilobjgroupgui', 'deleteMembers' ),
			array( 'ilrepositorysearchgui', '*' ),
			array( 'ilmemberexportgui', '*' ),
            array( 'ilcoursemembershipgui', '*' ),
            array( 'ilgroupmembershipgui', '*' ),
		);

		$tab_highlight = array( array( 'ilsubscriptiongui', '*' ), );

		if ($this->checkContext($a_part, $locations)) {
			$tabs = $DIC->tabs();

			$pl_obj = ilSubscriptionPlugin::getInstance();
			$tabs->removeSubTab('srsubscription');
			$tabs->activateTab('members');
			$this->ctrl->setTargetScript('ilias.php');
			$this->initBaseClass();
			$this->ctrl->setParameterByClass('msSubscriptionGUI', 'obj_ref_id', $_GET['ref_id']);

			$tabs->addSubTab('srsubscription', $pl_obj->txt('tab_usage_'
			                                                                . msConfig::getUsageType()), $this->ctrl->getLinkTargetByClass(array(
				'ilUIPluginRouterGUI',
				'msSubscriptionGUI',
			)), '', 'ilsubscriptiongui');

			if ($this->checkContext($a_part, $tab_highlight)) {
				$tabs->activateSubTab('srsubscription');
			}
		}
	}


	/**
	 * @description Check whether current context is in array. Array should look like
	 * $array = array(
	 *              array('ilpermissiongui', 'perm'),
	 *              array('ilreportsgui', 'show'),
	 *      ...
	 * );
	 *
	 * @param       $a_part
	 * @param array $context
	 *
	 * @return bool
	 */
	protected function checkContext($a_part, array $context) {
		if ($a_part != 'sub_tabs') {
			return false;
		}

		$check_cmd = in_array(array( $this->ctrl->getCmdClass(), $this->ctrl->getCmd() ), $context);
		$check_cmd_class = in_array(array( $this->ctrl->getCmdClass(), '*' ), $context);
		if (!$check_cmd AND !$check_cmd_class) {
			return false;
		}
		if (!in_array($this->ctrl->getContextObjType(), array( 'grp', 'crs' ))) {
			return false;
		}

		$ref_id = $_GET['ref_id'];
		if (!$this->access->checkAccess('write', '', $ref_id)) {
			return false;
		}

		if ($this->ctrl->getContextObjType() == 'grp' AND !msConfig::getValueByKey(msConfig::F_ACTIVATE_GROUPS)) {
			return false;
		}

		if (msConfig::isInIgnoredSubtree($_GET['ref_id'])) {
			return false;
		}

		return true;
	}


	public function gotoHook() {
		if (preg_match("/tokenreg_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
			$token = $matches[1];
			$this->initBaseClass();
			$this->ctrl->setTargetScript('./ilias.php');
			$this->ctrl->setParameterByClass('ilTokenRegistrationGUI', 'token', $token);
			$arr = array( 'ilUIPluginRouterGUI', 'subscrTriageGUI' );
			$this->ctrl->redirectByClass($arr);
		}

		if (preg_match("/subscr_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
			$token = $matches[1];
			$this->initBaseClass();
			$this->ctrl->setTargetScript('./ilias.php');
			$this->ctrl->setParameterByClass('subscrTriageGUI', 'token', $token);
			$arr = array( 'ilUIPluginRouterGUI', 'subscrTriageGUI' );

			$this->ctrl->redirectByClass($arr);
		}
	}


	/**
	 * @return array
	 */
	protected function getIgnoredSubTree() {
		if (!isset(self::$ignored_subtree)) {
			foreach (explode(',', trim(msConfig::getValueByKey('ignore_subtree'))) as $root_id) {
				if (!$root_id) {
					continue;
				}
				self::$ignored_subtree = array_merge(self::$ignored_subtree, $this->tree->getSubTree($this->tree->getNodeData($root_id), false));
			}
		}

		return self::$ignored_subtree;
	}


	protected function initBaseClass() {
		$this->ctrl->initBaseClass('ilUIPluginRouterGUI');
	}
}
