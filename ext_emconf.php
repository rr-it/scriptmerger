<?php

########################################################################
# Extension Manager/Repository config file for ext "scriptmerger".
#
# Auto generated 21-10-2010 23:35
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CSS/Javascript Minificator, Compressor And Merger',
	'description' => 'This extension minimizes the http requests by a simple merging of your css and javascript. The result files will be minified and compressed. Anything is highly configurable. The extension make usage of the "minify" and "jsminplus" projects.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '3.1.0',
	'dependencies' => '',
	'conflicts' => 'speedy,queo_speedup,js_css_optimizer,minify',
	'priority' => 'bottom',
	'loadOrder' => 'tstidy',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/scriptmerger/',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Stefan Galinski',
	'author_email' => 'stefan.galinski@gmail.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.1-5.3.99',
			'typo3' => '4.2.0-4.4.99',
		),
		'conflicts' => array(
			'speedy' => '',
			'queo_speedup' => '',
			'js_css_optimizer' => '',
			'minify' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:25:"class.tx_scriptmerger.php";s:4:"2c02";s:31:"class.tx_scriptmerger_cache.php";s:4:"717d";s:16:"example.htaccess";s:4:"c522";s:12:"ext_icon.gif";s:4:"2cfb";s:17:"ext_localconf.php";s:4:"1455";s:14:"ext_tables.php";s:4:"fa00";s:27:"configuration/constants.txt";s:4:"371a";s:23:"configuration/setup.txt";s:4:"23c8";s:14:"doc/manual.sxw";s:4:"b855";s:23:"resources/jsminplus.php";s:4:"48ed";s:35:"resources/minify/lib/Minify/CSS.php";s:4:"dc9e";s:48:"resources/minify/lib/Minify/CommentPreserver.php";s:4:"86ba";s:47:"resources/minify/lib/Minify/ImportProcessor.php";s:4:"5ce5";s:46:"resources/minify/lib/Minify/CSS/Compressor.php";s:4:"b514";s:47:"resources/minify/lib/Minify/CSS/UriRewriter.php";s:4:"5ab5";}',
	'suggests' => array(
	),
);

?>