<#1>
<?php
/**
 * @var $ilDB ilDB
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
if (!$ilDB->tableExists('rep_robj_xmsb_token')) {
	$ilDB->createTable("rep_robj_xmsb_token", $fields);
	$ilDB->addPrimaryKey("rep_robj_xmsb_token", array("id"));
    if($ilDB->tableExists('rep_robj_xmsb_token_seq')){
        $ilDB->dropTable('rep_robj_xmsb_token_seq', false);
    }
	$ilDB->createSequence("rep_robj_xmsb_token");
}

?>
<#2>
<?php
$ilDB->addTableColumn("rep_robj_xmsb_token", "local_role",
    array (
        'type' => 'integer',
        'length' => 4,
        'notnull' => true,
        'default' => 2
    ));
?>
<#3>
<?php
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
if (!$ilDB->tableExists('rep_robj_xmsb_invt')) {
	$ilDB->createTable("rep_robj_xmsb_invt", $fields);
	$ilDB->addPrimaryKey("rep_robj_xmsb_invt", array("id"));
    if($ilDB->tableExists('rep_robj_xmsb_invt_seq')){
        $ilDB->dropTable('rep_robj_xmsb_invt_seq', false);
    }
	$ilDB->createSequence("rep_robj_xmsb_invt");
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
/**
 * @var $ilDB ilDB
 */

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.ilSubscriptionPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');

msConfig::updateDB();
msConfig::set('use_email', true);
msConfig::set('system_user', 6);

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/AccountType/class.msAccountType.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/UserStatus/class.msUserStatus.php');
msSubscription::updateDB();
if ($ilDB->tableExists('rep_robj_xmsb_token')) {
	$set = $ilDB->query('SELECT * FROM rep_robj_xmsb_token');
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
	$ilDB->renameTable('rep_robj_xmsb_token', 'rep_robj_xmsb_tk_bak');
}
?>
<#7>
<?php
if ($ilDB->tableExists('xunibas_subs_type')) {
	$ilDB->dropTable("xunibas_subs_type", false);
}
?>
<#8>
<?php
//require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.ilSubscriptionPlugin.php');
//$pl = ilSubscriptionPlugin::getInstance();
//$pl->updateLanguageFiles();
?>
<#9>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
msSubscription::renameDBField('email', 'matching_string');
msSubscription::removeDBField('matriculation');
msSubscription::updateDB();
?>
<#10>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');
msConfig::set(msConfig::ENBL_INV, true);
?>
<#11>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');
msConfig::set(msConfig::F_SEND_MAILS, false);
?>
<#12>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
msSubscription::renameDBField('crs_ref_id', 'obj_ref_id');
msSubscription::updateDB();
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');
msConfig::set(msConfig::F_ACTIVATE_GROUPS, false);
?>
<#13>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
global $DIC;
msSubscription::updateDB();
$DIC->database()->manipulate('UPDATE ' . msSubscription::TABLE_NAME . ' SET context = ' . $ilDB->quote(msSubscription::CONTEXT_CRS));
?>
<#14>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php');
msConfig::set(msConfig::F_ACTIVATE_COURSES, true);
msConfig::set(msConfig::F_ACTIVATE_GROUPS, false);
?>
<#15>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php');
if (! $ilDB->tableColumnExists(msSubscription::TABLE_NAME, 'matching_string')) {
	$ilDB->modifyTableColumn(msSubscription::TABLE_NAME, 'matching_string', array(
		"length" => 1024,
	));
}
?>
