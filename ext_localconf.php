<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$extConfig_scriptmerger = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scriptmerger']);

if (TYPO3_MODE == 'FE') {
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] =
		'EXT:scriptmerger/class.tx_scriptmerger.php:tx_scriptmerger->main';

	if (!$extConfig_scriptmerger['disableOutputHook']) {
		$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] =
			'EXT:scriptmerger/class.tx_scriptmerger.php:tx_scriptmerger->main';
	}
}

?>
