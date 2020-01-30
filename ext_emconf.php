<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "scriptmerger".
 *
 * Auto generated 03-03-2015 09:43
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'CSS/Javascript Minificator, Compressor And Concatenator',
	'description' => 'This extension minimizes the http requests by concatenating your css and javascript. Furthermore the result can be minified and compressed. This whole process is highly configurable and is partly based on the "minify", "jsminplus" and "jsmin" projects.',
	'category' => 'fe',
	'version' => '6.0.3',
	'state' => 'stable',
	'uploadfolder' => false,
	'createDirs' => 'typo3temp/scriptmerger/',
	'clearcacheonload' => false,
	'author' => 'Stefan Galinski',
	'author_email' => 'stefan@sgalinski.de',
	'author_company' => '',
	'constraints' =>
		[
			'depends' =>
				[
					'typo3' => '8.7.0-9.5.99',
				],
			'conflicts' =>
				[
					'speedy' => '',
					'queo_speedup' => '',
					'js_css_optimizer' => '',
					'minify' => '',
				],
			'suggests' =>
				[
				],
		],
];

