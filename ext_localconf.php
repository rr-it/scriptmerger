<?php
/**
 *
 * Copyright notice
 *
 * (c) sgalinski Internet Services (https://www.sgalinski.de)
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

call_user_func(
	function ($extKey) {
		// post processing hook to clear any existing cache files if the button in
		// the backend is clicked (contains an age check)
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
			'SGalinski\Scriptmerger\user_ScriptmergerCacheHook->clearCachePostProc';

		// register the minify, compress and merge processes
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] =
			'SGalinski\Scriptmerger\user_ScriptmergerOutputHook->contentPostProcOutput';
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] =
			'SGalinski\Scriptmerger\user_ScriptmergerOutputHook->contentPostProcAll';

		// needs to be disabled for the frontend, otherwise the default exclude rule prevents any script files from merging
		$GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] = '0';

		// Configure the logger for scriptmerger errors
		$GLOBALS['TYPO3_CONF_VARS']['LOG']['SGalinski']['Scriptmerger'] = [
			'writerConfiguration' => [
				\TYPO3\CMS\Core\Log\LogLevel::WARNING => [
					\SGalinski\Scriptmerger\Log\Writer\SysLogWriter::class => [],
					\TYPO3\CMS\Core\Log\Writer\FileWriter::class => []
				]
			]
		];
	}, 'scriptmerger'
);
