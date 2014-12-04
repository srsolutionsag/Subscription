<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.ilSubscriptionPlugin.php');
if (! ilSubscriptionPlugin::checkPreconditions()) {
	//ilUtil::sendFailure('Subscription needs ActiveRecord (https://svn.ilias.de/svn/ilias/branches/sr/ActiveRecord) and ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)');
}
$id = 'subscription';
$version = '2.2.6';
$ilias_min_version = '4.2.0';
$ilias_max_version = '4.4.999';
$responsible = 'Fabian Schmid, Oskar Truffer - studer + raimann ag';
$responsible_mail = 'support@studer-raimann.ch';
?>