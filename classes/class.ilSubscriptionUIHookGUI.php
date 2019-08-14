<?php

/**
 * Class ilSubscriptionUIHookGUI
 *
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilSubscriptionUIHookGUI extends ilUIHookPluginGUI
{

    const TAB_SRSUBSCRIPTION = 'srsubscription';
    const TAB_MEMBERS = 'members';
    const CONTEXT_OBJECT_COURSE = 'crs';
    const CONTEXT_OBJECT_GROUP = 'grp';
    const PART_SUB_TABS = 'sub_tabs';
    const REF_ID = 'ref_id';
    /**
     * @var array
     */
    protected static $ignored_subtree = array();
    /**
     * @var ilSubscriptionPlugin
     */
    private $pl;
    /**
     * @var ilCtrl
     */
    private $ctrl;
    /**
     * @var ilAccessHandler
     */
    private $access;
    /**
     * @var ilTree
     */
    private $tree;


    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->tree = $DIC->repositoryTree();
        $this->pl = ilSubscriptionPlugin::getInstance();
    }


    /**
     * @param string $a_comp
     * @param string $a_part
     * @param array  $a_par
     */
    public function modifyGUI($a_comp, $a_part, $a_par = array())
    {
        global $DIC;

        $locations = array(
            array(ilObjGroupGUI::class, 'members'),
            array(ilObjCourseGUI::class, 'members'),
            array(ilCourseParticipantsGroupsGUI::class, 'show'),
            array(ilObjCourseGUI::class, 'membersGallery'),
            array(ilObjCourseGUI::class, 'mailMembers'),
            array(ilObjGroupGUI::class, 'membersGallery'),
            array(ilObjGroupGUI::class, 'mailMembers'),
            array(ilSessionOverviewGUI::class, 'listSessions'),
            array(msSubscriptionGUI::class, '*'),
            array(ilCourseParticipantsGroupsTableGUI::class, '*'),
            array(ilObjCourseGUI::class, 'editMember'),
            array(ilObjCourseGUI::class, 'updateMembers'),
            array(ilObjCourseGUI::class, 'deleteMembers'),
            array(ilObjCourseGUI::class, 'removeMembers'),
            array(ilObjGroupGUI::class, 'editMember'),
            array(ilObjGroupGUI::class, 'updateMembers'),
            array(ilObjGroupGUI::class, 'confirmDeleteMembers'),
            array(ilObjGroupGUI::class, 'deleteMembers'),
            array(ilRepositorySearchGUI::class, '*'),
            array(ilMemberExportGUI::class, '*'),
            array(ilCourseMembershipGUI::class, '*'),
            array(ilGroupMembershipGUI::class, '*'),
        );
        $locations = array_map(
            function ($c) {
                return array(strtolower($c[0]), $c[1]); // ::class no lower case
            }, $locations
        );

        $tab_highlight = array(array(msSubscriptionGUI::class, '*'),);

        if ($this->checkContext($a_part, $locations)) {
            $tabs = $DIC->tabs();

            $pl_obj = ilSubscriptionPlugin::getInstance();
            $tabs->removeSubTab(self::TAB_SRSUBSCRIPTION);
            $tabs->activateTab(self::TAB_MEMBERS);
            $this->ctrl->setTargetScript('ilias.php');
            $this->initBaseClass();
            $this->ctrl->setParameterByClass(msSubscriptionGUI::class, 'obj_ref_id', $_GET[self::REF_ID]);

            $tabs->addSubTab(
                self::TAB_SRSUBSCRIPTION, $pl_obj->txt('tab_usage_' . msConfig::getUsageType()), $this->ctrl->getLinkTargetByClass(
                array(
                    ilUIPluginRouterGUI::class,
                    msSubscriptionGUI::class,
                )
            ), ''
            );

            if ($this->checkContext($a_part, $tab_highlight)) {
                $tabs->activateSubTab(self::TAB_SRSUBSCRIPTION);
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
     * @param string $a_part
     * @param array  $context
     *
     * @return bool
     */
    protected function checkContext($a_part, array $context)
    {
        if ($a_part != self::PART_SUB_TABS) {
            return false;
        }

        if (msConfig::getUsageType() === msConfig::TYPE_NO_USAGE) {
            return false;
        }

        $check_cmd = in_array(array($this->ctrl->getCmdClass(), $this->ctrl->getCmd()), $context);
        $check_cmd_class = in_array(array($this->ctrl->getCmdClass(), '*'), $context);
        if (!$check_cmd && !$check_cmd_class) {
            return false;
        }
        if (!in_array($this->ctrl->getContextObjType(), array(self::CONTEXT_OBJECT_GROUP, self::CONTEXT_OBJECT_COURSE))) {
            return false;
        }

        $ref_id = $_GET[self::REF_ID];
        if (!$this->access->checkAccess('write', '', $ref_id)) {
            return false;
        }

        if ($this->ctrl->getContextObjType() == self::CONTEXT_OBJECT_COURSE && !msConfig::getValueByKey(msConfig::F_ACTIVATE_COURSES)) {
            return false;
        }

        if ($this->ctrl->getContextObjType() == self::CONTEXT_OBJECT_GROUP && !msConfig::getValueByKey(msConfig::F_ACTIVATE_GROUPS)) {
            return false;
        }

        if (msConfig::isInIgnoredSubtree($ref_id)) {
            return false;
        }

        return true;
    }


    public function gotoHook()
    {
        if (preg_match("/tokenreg_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
            $token = $matches[1];
            $this->initBaseClass();
            $this->ctrl->setTargetScript('./ilias.php');
            $this->ctrl->setParameterByClass(ilTokenRegistrationGUI::class, 'token', $token);
            $arr = array(ilUIPluginRouterGUI::class, subscrTriageGUI::class);
            $this->ctrl->redirectByClass($arr);
        }

        if (preg_match("/subscr_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
            $token = $matches[1];
            $this->initBaseClass();
            $this->ctrl->setTargetScript('./ilias.php');
            $this->ctrl->setParameterByClass(subscrTriageGUI::class, 'token', $token);
            $arr = array(ilUIPluginRouterGUI::class, subscrTriageGUI::class);

            $this->ctrl->redirectByClass($arr);
        }
    }


    /**
     * @return array
     */
    protected function getIgnoredSubTree()
    {
        if (!isset(self::$ignored_subtree)) {
            foreach (explode(',', trim(msConfig::getValueByKey(msConfig::F_IGNORE_SUBTREE))) as $root_id) {
                if (!$root_id) {
                    continue;
                }
                self::$ignored_subtree = array_merge(self::$ignored_subtree, $this->tree->getSubTree($this->tree->getNodeData($root_id), false));
            }
        }

        return self::$ignored_subtree;
    }


    protected function initBaseClass()
    {
        $this->ctrl->initBaseClass(ilUIPluginRouterGUI::class);
    }
}
