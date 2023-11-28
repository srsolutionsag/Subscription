<?php

/**
 * Class emSubscriptionTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class msSubscriptionTableGUI extends msModelObjectTableGUI
{
    public const DISABLED = 'disabled';
    public const CHECKED = 'checked';
    public const STD_ROLE = 2; // IL_CRS_MEMBER


    public function __construct($a_parent_obj, $a_parent_cmd)
    {
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setSelectAllCheckbox('obj');
    }

    protected function initTableFilter(): bool
    {
        $this->setDefaultOrderField('name');

        return false;
    }

    protected function initTableData()
    {
        $where = [
            'obj_ref_id' => $_GET['obj_ref_id'],
            'deleted' => false,
        ];
        $data = [];

        $user_ids = [];
        foreach (msSubscription::where($where)->orderBy('matching_string')->get() as $dat) {
            /**
             * @var msSubscription $dat
             */
            $dat->afterObjectLoad();
            // SW: Remove duplicated users, see bug https://jira.studer-raimann.ch/browse/PLMSC-11
            $user_id = $dat->user_status_object->getUsrId();
            if (in_array($user_id, $user_ids, true)) {
                continue;
            }
            if ($user_id !== null) {
                $user_ids[] = $user_id;
            }
            $row = (array) $dat->asStdClass();
            $row['name'] = $dat->lookupName();
            $row['status_sort'] = $this->pl->txt('main_user_status_' . $dat->getUserStatus());
            $row['in_ilias'] = $user_id !== 0 ? 1 : 0;
            $data[] = $row;
        }

        $this->setData($data);
    }

    protected function initTableColumns(): bool
    {
        $this->addColumn($this->pl->txt('main_tblh_subscribe'));

        if ($this->getMailUsage() && msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
            $this->addColumn($this->pl->txt('main_tblh_invite'));
        }
        if ($this->getMailUsage()) {
            $this->addColumn($this->pl->txt('main_tblh_email'), 'matching_string');
        }
        if ($this->getMatriculationUsage()) {
            $this->addColumn($this->pl->txt('main_tblh_matriculation'), 'matching_string');
        }
        if ($this->getMatriculationAndMailUsage()) {
            $this->addColumn($this->pl->txt('main_tblh_subscription_type'));
        }
        $this->addColumn($this->pl->txt('main_tblh_in_ilias'), 'in_ilias');
        if (msConfig::getValueByKey(msConfig::F_SHOW_NAMES)) {
            $this->addColumn($this->pl->txt('main_tblh_name'), 'name');
        }
        $this->addColumn($this->pl->txt('main_tblh_status'), 'status_sort');

        $this->addColumn($this->pl->txt('main_tblh_role'));

        return true;
    }

    protected function initTableProperties()
    {
        $this->table_title = $this->pl->txt('main_tblt_subscriptions');
        $this->table_id = 'srsubscr';
        $this->prefix = 'srsubscr';
    }

    protected function initFormActionsAndCmdButtons(): bool
    {
        $this->addCommandButton(
            msSubscriptionGUI::CMD_REMOVE_UNREGISTERED,
            $this->pl->txt('main_send_table_remove_unregistered')
        );
        $this->addCommandButton(msSubscriptionGUI::CMD_CLEAR, $this->pl->txt('main_send_table_clear'));
        if (msConfig::getValueByKey(msConfig::F_USE_EMAIL_FOR_USERS) && msConfig::getValueByKey(
            msConfig::F_ENABLE_SENDING_INVITATIONS
        )) {
            $this->addCommandButton(msSubscriptionGUI::CMD_TRIAGE, $this->pl->txt('main_send_table_usage_2'));
        } else {
            $this->addCommandButton(msSubscriptionGUI::CMD_TRIAGE, $this->pl->txt('main_send_table_usage_1'));
        }
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));

        return true;
    }

    protected function initTableRowTemplate(): bool
    {
        $this->setRowTemplate($this->pl->getDirectory() . '/templates/default/Subscription/tpl.subscription_row.html');
        return true;
    }

    protected function initLanguage(): bool
    {
        $this->pl = ilSubscriptionPlugin::getInstance();
        $this->translator = $this->pl;

        return true;
    }

    protected function fillTableRow($a_set): bool
    {
        if (!isset($a_set['id'])) {
            return false;
        }
        /**
         * @var msSubscription $msSubscription
         */
        $msSubscription = msSubscription::find($a_set['id']);
        $this->fillStandardFields($msSubscription);
        $this->fillActions($msSubscription);
        $this->tpl->setVariable('OPTIONS', $this->getRoleSelector($msSubscription->getRole()));

        return true;
    }
    
    protected function getRoleSelector(int $selected = self::STD_ROLE): string
    {
        $type = $this->parent_obj->getObj()->getType();
        switch ($type) {
            case 'crs':
                $roles = [
                    ilCourseParticipants::IL_CRS_MEMBER => $this->pl->txt(
                        'main_role_' . ilCourseParticipants::IL_CRS_MEMBER
                    ),
                    ilCourseParticipants::IL_CRS_TUTOR => $this->pl->txt(
                        'main_role_' . ilCourseParticipants::IL_CRS_TUTOR
                    ),
                    ilCourseParticipants::IL_CRS_ADMIN => $this->pl->txt(
                        'main_role_' . ilCourseParticipants::IL_CRS_ADMIN
                    ),
                ];
                break;
            case 'grp':
                $roles = [
                    ilGroupParticipants::IL_GRP_MEMBER => $this->pl->txt(
                        'main_role_' . ilGroupParticipants::IL_GRP_MEMBER
                    ),
                    ilGroupParticipants::IL_GRP_ADMIN => $this->pl->txt(
                        'main_role_' . ilGroupParticipants::IL_GRP_ADMIN
                    ),
                ];
                break;
        }

        $selection_menu = '';
        foreach ($roles as $value => $role) {
            $sel = ($selected == $value ? 'selected' : '');
            $selection_menu .= '<option value=\'' . $value . '\' ' . $sel . '>' . $role . '</option>';
        }

        return $selection_menu;
    }

    protected function fillStandardFields(msSubscription $msSubscription)
    {
        $msSubscription->afterObjectLoad();
        if ($this->getMailUsage()) {
            $this->tpl->setCurrentBlock('email');
            $this->tpl->setVariable(
                'EMAIL',
                ($msSubscription->getSubscriptionType()
                == msSubscription::TYPE_EMAIL ? $msSubscription->getMatchingString() : '&nbsp;')
            );
            $this->tpl->parseCurrentBlock();
        }
        if ($this->getMatriculationUsage()) {
            $this->tpl->setCurrentBlock('matriculation');
            $this->tpl->setVariable(
                'MATRICULATION',
                ($msSubscription->getSubscriptionType()
                == msSubscription::TYPE_MATRICULATION ? $msSubscription->getMatchingString() : '&nbsp;')
            );
            $this->tpl->parseCurrentBlock();
        }
        if ($this->getMatriculationAndMailUsage()) {
            $this->tpl->setCurrentBlock('type');
            $this->tpl->setVariable(
                'TYPE',
                $this->pl->txt('subscription_type_' . $msSubscription->getSubscriptionType())
            );
            $this->tpl->parseCurrentBlock();
        }
        $this->tpl->setVariable('STATUS', $this->pl->txt('main_user_status_' . $msSubscription->getUserStatus()));
        if (msConfig::getValueByKey('show_names')) {
            $this->tpl->setCurrentBlock('name');
            $this->tpl->setVariable('NAME', $msSubscription->lookupName());
            $this->tpl->parseCurrentBlock();
        }
        $this->tpl->setVariable('USR_ID', 'obj_' . $msSubscription->getId());
        $this->tpl->setVariable('STD_ROLE', msUserStatus::ROLE_MEMBER);
        if (!$this->getMailUsage() || !msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
            $this->tpl->setVariable('DISABLE_NOMAIL', 'nomail');
        }
        $this->tpl->setVariable('CMD_INVITE', msSubscriptionGUI::CMD_INVITE);

        $this->tpl->setVariable('CMD_SUBSCRIBE', msSubscriptionGUI::CMD_SUBSCRIBE);
    }

    protected function fillActions(msSubscription $msSubscription)
    {
        switch ($msSubscription->getUserStatus()) {
            case msUserStatus::STATUS_ALREADY_ASSIGNED:
                $this->tpl->setVariable('USER_EXISTS_STRING', $this->pl->txt('main_yes'));
                $this->tpl->setVariable('USER_EXISTS_STRING_CLASS', 'yes');
                $this->tpl->setVariable('DISABLED_SUB', self::DISABLED);
                $this->tpl->setVariable('DISABLED_INV', self::DISABLED);
                $this->tpl->setVariable('DISABLED_ROLE', self::DISABLED);
                $this->tpl->setVariable('CMD', msSubscriptionGUI::CMD_DELETE);
                break;
            case msUserStatus::STATUS_USER_CAN_BE_ASSIGNED:
                $this->tpl->setVariable('USER_EXISTS_STRING', $this->pl->txt('main_yes'));
                $this->tpl->setVariable('USER_EXISTS_STRING_CLASS', 'yes');
                $this->tpl->setVariable('DISABLED_INV', self::DISABLED);
                $this->tpl->setVariable('CHECKED_SUB', self::CHECKED);
                break;
            case msUserStatus::STATUS_ALREADY_INVITED:
                $this->tpl->setVariable('USER_EXISTS_STRING', $this->pl->txt('main_no'));
                $this->tpl->setVariable('USER_EXISTS_STRING_CLASS', 'no');
                $this->tpl->setVariable('DISABLED_SUB', self::DISABLED);
                $this->tpl->setVariable('DISABLED_ROLE', self::DISABLED);
                $this->tpl->setVariable('CMD', msSubscriptionGUI::CMD_KEEP);
                $this->tpl->setVariable('CMD_INVITE', msSubscriptionGUI::CMD_REINVITE);
                break;
            case msUserStatus::STATUS_USER_CAN_BE_INVITED:
                $this->tpl->setVariable('USER_EXISTS_STRING', $this->pl->txt('main_no'));
                $this->tpl->setVariable('USER_EXISTS_STRING_CLASS', 'no');
                $this->tpl->setVariable('DISABLED_SUB', self::DISABLED);
                if (msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
                    $this->tpl->setVariable('CHECKED_INV', self::CHECKED);
                } else {
                    $this->tpl->setVariable('CHECKED_INV', self::DISABLED);
                }
                break;
            case msUserStatus::STATUS_USER_NOT_INVITABLE:
            case msUserStatus::STATUS_USER_NOT_ASSIGNABLE:
                $this->tpl->setVariable('USER_EXISTS_STRING', $this->pl->txt('main_no'));
                $this->tpl->setVariable('USER_EXISTS_STRING_CLASS', 'no');
                $this->tpl->setVariable('DISABLED_SUB', self::DISABLED);
                $this->tpl->setVariable('CHECKED_INV', self::DISABLED);
                $this->tpl->setVariable('DISABLED_ROLE', self::DISABLED);
                $this->tpl->setVariable('CMD', msSubscriptionGUI::CMD_DELETE);
                break;
        }
    }

    protected function getMatriculationAndMailUsage(): bool
    {
        return msConfig::getUsageType() == msConfig::TYPE_USAGE_BOTH;
    }

    /**
     * @return bool|string
     */
    protected function getMatriculationUsage()
    {
        return msConfig::getValueByKey('use_matriculation');
    }

    /**
     * @return bool|string
     */
    protected function getMailUsage()
    {
        return msConfig::getValueByKey('use_email');
    }
}
