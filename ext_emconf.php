<?php

########################################################################
# Extension Manager/Repository config file for ext: "scriptmerger"
#
# Auto generated 26-06-2009 18:43
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CSS/Javascript Minificator, Compressor And Merger',
	'description' => 'You need more speed? If you can answer with question with "Sure!", then this extension is made for you! It minimizes the http requests by minifiing, compressing and merging of the css and javascript files on your site. The extension make usage of the "minify" project.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '2.6.5',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'bottom',
	'loadOrder' => 'tstidy',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
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
			'php' => '5.2.1-5.2.99',
			'typo3' => '4.2.0-4.3.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:25:"class.tx_scriptmerger.php";s:4:"0fa0";s:21:"ext_conf_template.txt";s:4:"0557";s:12:"ext_icon.gif";s:4:"2cfb";s:17:"ext_localconf.php";s:4:"3840";s:31:"resources/jsmin/jsmin-1.1.1.php";s:4:"dc0c";s:35:"resources/csstidy/class.csstidy.php";s:4:"7def";s:41:"resources/csstidy/class.csstidy_ctype.php";s:4:"ad91";s:44:"resources/csstidy/class.csstidy_optimise.php";s:4:"0990";s:41:"resources/csstidy/class.csstidy_print.php";s:4:"499a";s:30:"resources/csstidy/data.inc.php";s:4:"5164";s:14:"doc/manual.sxw";s:4:"1908";}',
	'suggests' => array(
	),
);

?>