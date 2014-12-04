<?php
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');

/**
 * Class ilSubscriptionUIHookGUI
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionUIHookGUI extends ilUIHookPluginGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var $ilTabs
	 */
	protected $tabs;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	function __construct() {
		global $ilCtrl, $ilTabs, $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->access = $ilAccess;
		$this->pl = ilSubscriptionPlugin::getInstance();
	}


	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 */
	function modifyGUI($a_comp, $a_part, $a_par = array()) {
		$locations = array(
			array( 'ilobjcoursegui', 'members' ),
			array( 'ilcourseparticipantsgroupsgui', 'show' ),
			array( 'ilobjcoursegui', 'membersGallery' ),
			array( 'ilobjcoursegui', 'mailMembers' ),
			array( 'ilsessionoverviewgui', 'listSessions' ),
			array( 'iluimasssubscriptiongui', 'members' ),
			array( 'iluimasssubscriptiongui', 'membersGallery' ),
			array( 'ilcourseeditparticipantstablegui', '*' ),
			array( 'ilsubscriptiongui', '*' ),
			array( 'ilobjcoursegui', 'editMember' ),
			array( 'ilobjcoursegui', 'updateMembers' ),
			array( 'ilobjcoursegui', 'deleteMembers' ),
			array( 'ilobjcoursegui', 'removeMembers' ),
			array( 'ilrepositorysearchgui', '*' ),
			array( 'ilmemberexportgui', '*' ),
		);
		$tab_highlight = array( array( 'ilsubscriptiongui', '*' ), );
		if ($this->checkContext($a_part, $locations)) {
			$ref_id = $_GET['ref_id'];
			if (! $this->access->checkAccess('write', '', $ref_id, 'crs')) {
				return;
			}
			$pl_obj = ilSubscriptionPlugin::getInstance();
			$this->tabs->removeSubTab('srsubscription');
			$this->tabs->setTabActive('members');
			$this->ctrl->setTargetScript('ilias.php');
			$this->ctrl->initBaseClass('ilRouterGUI');
			$this->ctrl->setParameterByClass('msSubscriptionGUI', 'crs_ref_id', $_GET['ref_id']);
			$this->tabs->addSubTab('srsubscription', $pl_obj->txt('tab_usage_' . msConfig::getUsageType()), $this->ctrl->getLinkTargetByClass(array(
				'ilRouterGUI',
				'msSubscriptionGUI'
			)), '', 'ilsubscriptiongui');
			if ($this->checkContext($a_part, $tab_highlight)) {
				$this->tabs->activateSubTab('srsubscription');
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
	private function checkContext($a_part, array $context) {
        global $tree;
        $ignore_subtree = array();
        foreach(explode(',', trim(msConfig::get('ignore_subtree'))) as $root_id)
        {
            $ignore_subtree = array_merge($ignore_subtree, $tree->getSubTree($tree->getNodeData($root_id), false));
        }

		return ($a_part == 'sub_tabs'

			AND (in_array(array( $this->ctrl->getCmdClass(), $this->ctrl->getCmd() ), $context) OR in_array(array(
					$this->ctrl->getCmdClass(),
					'*'
				), $context))
            AND (!in_array($_GET['ref_id'], $ignore_subtree))
        );
	}


	public function gotoHook() {
		if (preg_match("/tokenreg_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
			$token = $matches[1];
			$this->ctrl->initBaseClass('ilRouterGUI');
			$this->ctrl->setTargetScript('/ilias.php');
			$this->ctrl->setParameterByClass('ilTokenRegistrationGUI', 'token', $token);
			$arr = array( 'ilRouterGUI', 'ilTokenRegistrationGUI' );
			$this->ctrl->redirectByClass($arr);
		}

		if (preg_match("/subscr_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
			$token = $matches[1];
			$this->ctrl->initBaseClass('ilRouterGUI');
			$this->ctrl->setTargetScript('/ilias.php');
			$this->ctrl->setParameterByClass('subscrTriageGUI', 'token', $token);
			$arr = array( 'ilRouterGUI', 'subscrTriageGUI' );
			$this->ctrl->redirectByClass($arr);
		}
	}
}

?>
