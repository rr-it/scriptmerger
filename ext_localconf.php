<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// post processing hook to clear any existing cache files if the button in
// the backend is clicked (contains an age check)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
	'SGalinski\Scriptmerger\user_ScriptmergerCacheHook->clearCachePostProc';

// register the minify, compress and merge processes
if (TYPO3_MODE == 'FE') {
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] =
		'SGalinski\Scriptmerger\user_ScriptmergerOutputHook->contentPostProcOutput';
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] =
		'SGalinski\Scriptmerger\user_ScriptmergerOutputHook->contentPostProcAll';
}

// needs to be disabled for the frontend, otherwise the default exclude rule prevents any script files from merging
$TYPO3_CONF_VARS['FE']['versionNumberInFilename'] = '';
$TYPO3_CONF_VARS['FE']['compressionLevel'] = '0';

// Configure the logger for scriptmerger errors
$GLOBALS['TYPO3_CONF_VARS']['LOG']['SGalinski']['Scriptmerger'] = [
	'writerConfiguration' => [
		\TYPO3\CMS\Core\Log\LogLevel::WARNING => [
			\SGalinski\Scriptmerger\Log\Writer\SysLogWriter::class => [],
			\TYPO3\CMS\Core\Log\Writer\FileWriter::class => []
		]
	]
];
?>
