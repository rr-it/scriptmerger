<?php

########################################################################
# Extension Manager/Repository config file for ext "scriptmerger".
#
# Auto generated 10-09-2011 16:30
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CSS/Javascript Minificator, Compressor And Concatenator',
	'description' => 'This extension minimizes the http requests by concatenating your css and javascript. Furthermore the result can be minified and compressed. This whole process is highly configurable and is partly based on the "minify", "jsminplus" and "jsmin" projects.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '3.2.1',
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
			'typo3' => '4.2.0-4.5.99',
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
	'_md5_values_when_last_written' => 'a:15:{s:25:"class.tx_scriptmerger.php";s:4:"7b98";s:31:"class.tx_scriptmerger_cache.php";s:4:"ac45";s:16:"example.htaccess";s:4:"7469";s:12:"ext_icon.gif";s:4:"2cfb";s:17:"ext_localconf.php";s:4:"1455";s:14:"ext_tables.php";s:4:"9927";s:27:"configuration/constants.txt";s:4:"038d";s:23:"configuration/setup.txt";s:4:"5fdd";s:14:"doc/manual.sxw";s:4:"4c18";s:23:"resources/jsminplus.php";s:4:"13d4";s:35:"resources/minify/lib/Minify/CSS.php";s:4:"388d";s:48:"resources/minify/lib/Minify/CommentPreserver.php";s:4:"9762";s:47:"resources/minify/lib/Minify/ImportProcessor.php";s:4:"16f4";s:46:"resources/minify/lib/Minify/CSS/Compressor.php";s:4:"92c8";s:47:"resources/minify/lib/Minify/CSS/UriRewriter.php";s:4:"d1db";}',
	'suggests' => array(
	),
);

?>