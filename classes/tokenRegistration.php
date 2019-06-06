<?php
/**
 * Redirect to triage
 */
$link = strstr(ILIAS_HTTP_PATH, 'Customizing', true)
	. '/Customizing/global/plugins/Services/UIComponent'
	. '/UserInterfaceHook/Subscription/classes/triage.php?token=' . $_GET['token'];
header('Location: ' . $link);
