<#1>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
/**
 * @var ilDBInterface $ilDB
 */
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'course_ref' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ),
    'email' => array(
        'type' => 'text',
        'length' => 50,
        'notnull' => false
    ),
    'token' => array(
        'type' => 'text',
        'length' => 100,
        'notnull' => false
    ),
    'deleted' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    )
);
if (!$ilDB->tableExists(msToken::TABLE_NAME)) {
	$ilDB->createTable(msToken::TABLE_NAME, $fields);
	$ilDB->addPrimaryKey(msToken::TABLE_NAME, array("id"));
    if($ilDB->tableExists(msToken::TABLE_NAME . '_seq')){
        $ilDB->dropTable(msToken::TABLE_NAME . '_seq');
    }
	$ilDB->createSequence(msToken::TABLE_NAME);
}

?>
<#2>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
$ilDB->addTableColumn(msToken::TABLE_NAME, "local_role",
    array (
        'type' => 'integer',
        'length' => 4,
        'notnull' => true,
        'default' => 2
    ));
?>
<#3>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'course_ref' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	),
	'invitation_type' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	)
);
if (!$ilDB->tableExists(msInvitation::TABLE_NAME)) {
	$ilDB->createTable(msInvitation::TABLE_NAME, $fields);
	$ilDB->addPrimaryKey(msInvitation::TABLE_NAME, array("id"));
    if($ilDB->tableExists(msInvitation::TABLE_NAME . '_seq')){
        $ilDB->dropTable(msInvitation::TABLE_NAME . '_seq');
    }
	$ilDB->createSequence(msInvitation::TABLE_NAME);
}
?>
<#4>
<?php
/* DELETED */
?>
<#5>
<?php
/* DELETED */
?>
<#6>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';

msConfig::updateDB();
msConfig::set('use_email', true);
msConfig::set('system_user', 6);

msSubscription::updateDB();
if ($ilDB->tableExists(msToken::TABLE_NAME)) {
	$set = $ilDB->query('SELECT * FROM ' . msToken::TABLE_NAME);
	while ($rec = $ilDB->fetchObject($set)) {
		$msSubscription = new msSubscription();
		$msSubscription->setObjRefId($rec->course_ref);
		$msSubscription->setMatchingString($rec->email);
		$msSubscription->setToken($rec->token);
		$msSubscription->setDeleted($rec->deleted);
		$msSubscription->setRole(msUserStatus::ROLE_MEMBER);
		$msSubscription->setInvitationsSent(1);
		$msSubscription->create();
	}
	if ($ilDB->tableExists('rep_robj_xmsb_tk_bak')) {
		$ilDB->dropTable("rep_robj_xmsb_tk_bak", false);
	}
	$ilDB->renameTable(msToken::TABLE_NAME, 'rep_robj_xmsb_tk_bak');
}
?>
<#7>
<?php
//require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
//$ilDB->dropTable(xUnibasSubsType::TABLE_NAME, false);
?>
<#8>
<?php
//require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
//$pl = ilSubscriptionPlugin::getInstance();
//$pl->updateLanguageFiles();
?>
<#9>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
msSubscription::renameDBField('email', 'matching_string');
msSubscription::removeDBField('matriculation');
msSubscription::updateDB();
?>
<#10>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
msConfig::set(msConfig::F_ENABLE_SENDING_INVITATIONS, true);
?>
<#11>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
msConfig::set(msConfig::F_SEND_MAILS_FOR_COURSE_SUBSCRIPTION, false);
?>
<#12>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
msSubscription::renameDBField('crs_ref_id', 'obj_ref_id');
msSubscription::updateDB();

msConfig::set(msConfig::F_ACTIVATE_GROUPS, false);
?>
<#13>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
global $DIC;
msSubscription::updateDB();
$DIC->database()->manipulate('UPDATE ' . msSubscription::TABLE_NAME . ' SET context = ' . $ilDB->quote(msSubscription::CONTEXT_CRS, ilDBConstants::T_INTEGER));
?>
<#14>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
msConfig::set(msConfig::F_ACTIVATE_COURSES, true);
msConfig::set(msConfig::F_ACTIVATE_GROUPS, false);
?>
<#15>
<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php';
if (! $ilDB->tableColumnExists(msSubscription::TABLE_NAME, 'matching_string')) {
	$ilDB->modifyTableColumn(msSubscription::TABLE_NAME, 'matching_string', array(
		"length" => 1024,
	));
}
?>
