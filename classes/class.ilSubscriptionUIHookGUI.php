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
    public const TAB_SRSUBSCRIPTION = 'srsubscription';
    public const TAB_MEMBERS = 'members';
    public const CONTEXT_OBJECT_COURSE = 'crs';
    public const CONTEXT_OBJECT_GROUP = 'grp';
    public const PART_SUB_TABS = 'sub_tabs';
    public const REF_ID = 'ref_id';
    /**
     * @var array
     */
    protected static $ignored_subtree = [];
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
        if ($DIC->offsetExists('ilCtrl')) {
            $this->ctrl = $DIC->ctrl();
        }
        if ($DIC->offsetExists('ilAccess')) {
            $this->access = $DIC->access();
        }
        if ($DIC->offsetExists('tree')) {
            $this->tree = $DIC->repositoryTree();
        }
    }

    public function modifyGUI(
        string $a_comp,
        string $a_part,
        array $a_par = []
    ): void {
        global $DIC;

        $locations = [
            [ilObjGroupGUI::class, 'members'],
            [ilObjCourseGUI::class, 'members'],
            [ilCourseParticipantsGroupsGUI::class, 'show'],
            [ilObjCourseGUI::class, 'membersGallery'],
            [ilObjCourseGUI::class, 'mailMembers'],
            [ilObjGroupGUI::class, 'membersGallery'],
            [ilObjGroupGUI::class, 'mailMembers'],
            [ilSessionOverviewGUI::class, 'listSessions'],
            [msSubscriptionGUI::class, '*'],
            [ilCourseParticipantsGroupsTableGUI::class, '*'],
            [ilObjCourseGUI::class, 'editMember'],
            [ilObjCourseGUI::class, 'updateMembers'],
            [ilObjCourseGUI::class, 'deleteMembers'],
            [ilObjCourseGUI::class, 'removeMembers'],
            [ilObjGroupGUI::class, 'editMember'],
            [ilObjGroupGUI::class, 'updateMembers'],
            [ilObjGroupGUI::class, 'confirmDeleteMembers'],
            [ilObjGroupGUI::class, 'deleteMembers'],
            [ilRepositorySearchGUI::class, '*'],
            [ilMemberExportGUI::class, '*'],
            [ilCourseMembershipGUI::class, '*'],
            [ilGroupMembershipGUI::class, '*'],
        ];
        $locations = array_map(
            function ($c): array {
                return [strtolower($c[0]), $c[1]]; // ::class no lower case
            },
            $locations
        );

        $tab_highlight = [[msSubscriptionGUI::class, '*'],];

        if ($this->checkContext($a_part, $locations)) {
            $tabs = $DIC->tabs();

            $pl_obj = ilSubscriptionPlugin::getInstance();
            $tabs->removeSubTab(self::TAB_SRSUBSCRIPTION);
            $tabs->activateTab(self::TAB_MEMBERS);
            $this->ctrl->setTargetScript('ilias.php');
            $this->ctrl->setParameterByClass(msSubscriptionGUI::class, 'obj_ref_id', $_GET[self::REF_ID]);

            $tabs->addSubTab(
                self::TAB_SRSUBSCRIPTION,
                $pl_obj->txt('tab_usage_' . msConfig::getUsageType()),
                $this->ctrl->getLinkTargetByClass(
                    [
                        ilUIPluginRouterGUI::class,
                        msSubscriptionGUI::class,
                    ]
                ),
                ''
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
     *
     */
    protected function checkContext($a_part, array $context): bool
    {
        if ($a_part != self::PART_SUB_TABS) {
            return false;
        }

        if (msConfig::getUsageType() === msConfig::TYPE_NO_USAGE) {
            return false;
        }

        $array = [strtolower($this->ctrl->getCmdClass()), $this->ctrl->getCmd()];
        $check_cmd = in_array($array, $context);
        $check_cmd_class = in_array([$this->ctrl->getCmdClass(), '*'], $context);
        if (!$check_cmd && !$check_cmd_class) {
            return false;
        }
        if (!in_array($this->ctrl->getContextObjType(), [self::CONTEXT_OBJECT_GROUP, self::CONTEXT_OBJECT_COURSE])) {
            return false;
        }

        $ref_id = $_GET[self::REF_ID];
        if (!$this->access->checkAccess('write', '', $ref_id)) {
            return false;
        }

        if ($this->ctrl->getContextObjType() == self::CONTEXT_OBJECT_COURSE && !msConfig::getValueByKey(
            msConfig::F_ACTIVATE_COURSES
        )) {
            return false;
        }

        if ($this->ctrl->getContextObjType() == self::CONTEXT_OBJECT_GROUP && !msConfig::getValueByKey(
            msConfig::F_ACTIVATE_GROUPS
        )) {
            return false;
        }

        foreach ($context as $item) {
            if (strtolower($this->ctrl->getCmdClass()) === $item[0]
                && ($this->ctrl->getCmd() === $item[1] || $item[1] === '*')) {
                return true;
            }
        }

        return !msConfig::isInIgnoredSubtree($ref_id);
    }

    public function gotoHook(): void
    {
        if (preg_match("/tokenreg_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
            $token = $matches[1];
            //            $this->initBaseClass();
            $this->ctrl->setTargetScript('./ilias.php');
            $this->ctrl->setParameterByClass(ilTokenRegistrationGUI::class, 'token', $token);
            $arr = [ilUIPluginRouterGUI::class, subscrTriageGUI::class];
            $this->ctrl->redirectByClass($arr);
        }

        if (preg_match("/subscr_([0-9a-zA-Z]*)/uim", $_GET['target'], $matches)) {
            $token = $matches[1];
            //            $this->initBaseClass();
            $this->ctrl->setTargetScript('./ilias.php');
            $this->ctrl->setParameterByClass(subscrTriageGUI::class, 'token', $token);
            $arr = [ilUIPluginRouterGUI::class, subscrTriageGUI::class];

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
                if ($root_id === '' || $root_id === '0') {
                    continue;
                }
                self::$ignored_subtree = array_merge(
                    self::$ignored_subtree,
                    $this->tree->getSubTree($this->tree->getNodeData($root_id), false)
                );
            }
        }

        return self::$ignored_subtree;
    }

    protected function initBaseClass()
    {
        //        $this->ctrl->initBaseClass(ilUIPluginRouterGUI::class);
    }
}
