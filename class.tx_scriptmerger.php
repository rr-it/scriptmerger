<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Stefan Galinski <stefan.galinski@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This file contains the main processing class of the extension "scriptmerger". It
 * handles the parsing and replacing of the css and javascript files. Also it contains
 * methods for compressing, minifiing and merging of such files. The whole functionality
 * uses extensivly the functionality of the project minify.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */

/** Minify: Import Processor */
require_once(t3lib_extMgm::extPath('scriptmerger') .
	'resources/minify/lib/Minify/ImportProcessor.php');

/** Minify: CSS Minificator */
require_once(t3lib_extMgm::extPath('scriptmerger') .
	'resources/minify/lib/Minify/CSS.php');

/** Minify: JSMin+ */
require_once(t3lib_extMgm::extPath('scriptmerger') .
	'resources/minify/lib/JSMinPlus.php');

/**
 * This class contains the parsing and replacing functionality of css and javascript files.
 * Furthermore several wrapper methods to the project minify are available to minify, merge
 * and compress such files.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class tx_scriptmerger {
	/* @var $tempDirectory array directories for minified, compressed and merged files */
	private $tempDirectories = '';

	/** @var $extConfig array holds the extension configuration */
	private $extConfig = array();

	/* @var $conditionalComments array holds the conditional comments */
	private $conditionalComments = array();

	/**
	 * holds the javascript code
	 *
	 * Structure:
	 * - $relation (rel attribute)
	 *   - $media (media attribute)
	 *     - $file
	 *       |-content => string
	 *       |-basename => string (basename of $file without file prefix)
	 *       |-minify-ignore => bool
	 *       |-merge-ignore => bool
	 *
	 * @var $css array
	 */
	private $css = array();

	/**
	 * holds the javascript code 
	 *
	 * Structure:
	 * - $file
	 *   |-content => string
	 *   |-basename => string (basename of $file without file prefix)
	 *   |-minify-ignore => bool
	 *   |-merge-ignore => bool
	 *
	 * @var $javascript array
	 */
	private $javascript = array();

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// define temporary directories
		$this->tempDirectories = array (
			'main' => PATH_site . 'typo3temp/scriptmerger/',
			'temp' => PATH_site . 'typo3temp/scriptmerger/temp/',
			'minified' => PATH_site . 'typo3temp/scriptmerger/minified/',
			'compressed' => PATH_site . 'typo3temp/scriptmerger/compressed/',
			'merged' => PATH_site . 'typo3temp/scriptmerger/merged/'
		);

		// create missing directories
		foreach ($this->tempDirectories as $directory) {
			if (!is_dir($directory)) {
				mkdir($directory);
			}
		}

		// prepare the extension configuration
		$this->prepareExtensionConfiguration();
	}

	/**
	 * Just a wrapper for the main function! It's used for the contentPostProc-all hook.
	 *
	 * @return void
	 */
	public function contentPostProcAll() {
		// don't use this hook if no uncached informations are available
		if ($GLOBALS['TSFE']->config['config']['no_cache'] != 1 &&
			t3lib_div::_GP('no_cache') != 1
		) {
			return;
		}

		$this->main();
	}

	/**
	 * Just a wrapper for the main function!  It's used for the contentPostProc-cache hook.
	 *
	 * @return void
	 */
	public function contentPostProcCached() {
		$this->main();
	}

	/**
	 * This method fetches and prepares the extension configuration.
	 * 
	 * @return void
	 */
	protected function prepareExtensionConfiguration() {
		// global extension configuration
		$this->extConfig = unserialize(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scriptmerger']
		);

		// typoscript extension configuration
		$tsSetup = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_scriptmerger.'];
		if (is_array($tsSetup)) {
			foreach ($tsSetup as $key => $value) {
				$this->extConfig[$key] = $value;
			}
		}
		
		// prepare ignore expressions
		if ($this->extConfig['css.']['minify.']['ignore'] !== '') {
			$this->extConfig['css.']['minify.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['css.']['minify.']['ignore']) .
				').*/isU';
		}

		if ($this->extConfig['css.']['compress.']['ignore'] !== '') {
			$this->extConfig['css.']['compress.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['css.']['compress.']['ignore']) .
				').*/isU';
		}

		if ($this->extConfig['css.']['merge.']['ignore'] !== '') {
			$this->extConfig['css.']['merge.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['css.']['merge.']['ignore']) .
				').*/isU';
		}

		if ($this->extConfig['javascript.']['minify.']['ignore'] !== '') {
			$this->extConfig['javascript.']['minify.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['javascript.']['minify.']['ignore']) .
				').*/isU';
		}

		if ($this->extConfig['javascript.']['compress.']['ignore'] !== '') {
			$this->extConfig['javascript.']['compress.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['javascript.']['compress.']['ignore']) .
				').*/isU';
		}

		if ($this->extConfig['javascript.']['merge.']['ignore'] !== '') {
			$this->extConfig['javascript.']['merge.']['ignore'] = '/.*(' .
				str_replace(',', '|', $this->extConfig['javascript.']['merge.']['ignore']) .
				').*/isU';
		}
	}

	/**
	 * Contains the process logic of the whole plugin!
	 *
	 * @return void
	 */
	protected function main() {
		if ($this->extConfig['css.']['enable'] === '1') {
			// save the conditional comments
			$this->getConditionalComments();

			// fetch all remaining css contents
			$this->getCSSfiles();

			// minify, compress and merging
			foreach ($this->css as $relation => $cssByRelation) {
				foreach ($cssByRelation as $media => $cssByMedia) {
					$mergedContent = '';
					$files = '';
					$firstFreeIndex = -1;
					foreach ($cssByMedia as $index => $cssProperties) {
						$newFile = '';

						// file should be minified
						if ($this->extConfig['css.']['minify.']['enable'] === '1' &&
							!$cssProperties['minify-ignore']
						) {
							$newFile = $this->minifyCSSfile($cssProperties);
						}

						// file should be merged
						if ($this->extConfig['css.']['merge.']['enable'] === '1' &&
							!$cssProperties['merge-ignore']
						) {
							if ($firstFreeIndex < 0) {
								$firstFreeIndex = $index;
							}

							// add content and filename
							$mergedContent .= $cssProperties['content'] . "\n";
							$files .= $cssProperties['basename'];

							// remove file from array
							unset($this->css[$relation][$media][$index]);

							// we doesn't need to compress or add a new file to the array,
							// because the last one will finally not be needed anymore
							continue;
						}

						// file should be compressed instead?
						if ($this->extConfig['css.']['compress.']['enable'] === '1' &&
							function_exists('gzcompress') && !$cssProperties['compress-ignore']
						) {
							$newFile = $this->compressCSSfile($cssProperties);
						}

						// minification or compression was used
						if ($newFile !== '') {
							// remove old entry
							unset($this->css[$relation][$media][$index]);

							// add new entry
							$this->css[$relation][$media][$index]['file'] = $newFile;
							$this->css[$relation][$media][$index]['content'] =
								$cssProperties['content'];
							$this->css[$relation][$media][$index]['basename'] =
								$cssProperties['basename'];
						}
					}

					// save merged content inside a new file
					if ($this->extConfig['css.']['merge.']['enable'] === '1') {
						// create filename
						$newBasename = md5($files) . '.merged';

						// create property array
						$properties = array (
							'content' => $mergedContent,
							'basename' => $newBasename
						);

						// file should be compressed
						$newFile = '';
						if ($this->extConfig['css.']['compress.']['enable'] === '1' &&
							function_exists('gzcompress')
						) {
							$newFile = $this->compressCSSfile($properties);
						} else {
							$newFile = $this->tempDirectories['merged'] .
								$properties['basename'] . '.css';
							t3lib_div::writeFile($newFile, $properties['content']);
						}

						// add new entry
						$this->css[$relation][$media][$firstFreeIndex]['file'] = $newFile;
						$this->css[$relation][$media][$firstFreeIndex]['content'] =
							$properties['content'];
						$this->css[$relation][$media][$firstFreeIndex]['basename'] =
							$properties['basename'];
					}
				}
			}
			
			// write the conditional comments and possibly merged css files back to the document
			$this->writeCSStoDocument();
			$this->writeConditionalCommentsToDocument();
		}

		if ($this->extConfig['javascript.']['enable'] === '1') {
			// fetch all javascript content
			$this->getJavascriptFiles();

			// minify, compress and merging
			$mergedContent = '';
			$files = '';
			$firstFreeIndex = -1;
			foreach ($this->javascript as $index => $javascriptProperties) {
				$newFile = '';

				// file should be minified
				if ($this->extConfig['javascript.']['minify.']['enable'] === '1' &&
					!$javascriptProperties['minify-ignore']
				) {
					$newFile = $this->minifyJavascriptFile($javascriptProperties);
				}

				// file should be merged
				if ($this->extConfig['javascript.']['merge.']['enable'] === '1' &&
					!$javascriptProperties['merge-ignore']
				) {
					if ($firstFreeIndex < 0) {
						$firstFreeIndex = $index;
					}

					// add content and filename
					$mergedContent .= $javascriptProperties['content'] . "\n";
					$files .= $javascriptProperties['basename'];

					// remove file from array
					unset($this->javascript[$index]);

					// we doesn't need to compress or add a new file to the array,
					// because the last one will finally not be needed anymore
					continue;
				}

				// file should be compressed instead?
				if ($this->extConfig['javascript.']['compress.']['enable'] === '1' &&
					function_exists('gzcompress') && !$javascriptProperties['compress-ignore']
				) {
					$newFile = $this->compressJavascriptFile($javascriptProperties);
				}

				// minification or compression was used
				if ($newFile !== '') {
					// remove old entry
					unset($this->javascript[$index]);

					// add new entry
					$this->javascript[$index]['file'] = $newFile;
					$this->javascript[$index]['content'] = $javascriptProperties['content'];
					$this->javascript[$index]['basename'] = $javascriptProperties['basename'];
				}
			}

			// save merged content inside a new file
			if ($this->extConfig['javascript.']['merge.']['enable'] === '1') {
				// create filename
				$newBasename = md5($files) . '.merged';

				// create property array
				$properties = array (
					'content' => $mergedContent,
					'basename' => $newBasename
				);

				// file should be compressed
				$newFile = '';
				if ($this->extConfig['javascript.']['compress.']['enable'] === '1' &&
					function_exists('gzcompress')
				) {
					$newFile = $this->compressJavascriptFile($properties);
				} else {
					$newFile = $this->tempDirectories['merged'] .
						$properties['basename'] . '.js';
					t3lib_div::writeFile($newFile, $properties['content']);
				}

				// add new entry
				$this->javascript[$firstFreeIndex]['file'] = $newFile;
				$this->javascript[$firstFreeIndex]['content'] = $properties['content'];
				$this->javascript[$firstFreeIndex]['basename'] = $properties['basename'];
			}

			// write javascript content back to the document
			$this->writeJavascriptToDocument();
		}
	}

	/**
	 * This method parses the output content and saves any found conditional comments
	 * into the "conditionalComments" class property. The output content is cleaned
	 * up of the found results.
	 *
	 * @return void
	 */
	protected function getConditionalComments() {
		// parse the conditional comments
		$pattern = '/<!--\[if.+?<!\[endif\]-->\s*/is';
		preg_match_all($pattern, $GLOBALS['TSFE']->content, $this->conditionalComments);
		//t3lib_div::debug($this->conditionalComments);
		if (!$this->conditionalComments[0]) {
			return;
		}

		// remove the conditional comments from the output content
		$GLOBALS['TSFE']->content = preg_replace(
			$pattern,
			'',
			$GLOBALS['TSFE']->content,
			count($this->conditionalComments[0])
		);
	}
	
	/**
	 * This method writes the conditional comments back into the final output content.
	 * 
	 * @return void
	 */
	protected function writeConditionalCommentsToDocument() {
		// write conditional comments into the output content
		$pattern = '/<\/head>/is';
		$replace = implode("\n", $this->conditionalComments[0]) . "\n" . '\0';
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, $replace, $GLOBALS['TSFE']->content);
	}

	/**
	 * This method parses the output content and saves any found css files or inline code
	 * into the "css" class property. The output content is cleaned up of the found results.
	 *
	 * @return void
	 */
	protected function getCSSfiles() {
		// parse all available css code inside link and style tags
		$cssTags = array();
		$pattern = '/' .
			'<(link|style)' .	// This expression includes any link or style nodes
				'(?=.+?(?:type="(text\/css)"|>))' .	// which have the type text/css.
				'(?=.+?(?:media="(.*?)"|>))' .		// It fetches the media attribute
				'(?=.+?(?:href="(.*?)"|>))' .		// and the href attribute
				'(?=.+?(?:rel="(.*?)"|>))' .		// and the rel attribute of the node.
			'.+?\2.+?' .				// Finally we finish the parsing of the opening tag
			'(?:\/>|<\/style>)\s*' .	// until the possible closing tag.
			'/is';
		preg_match_all($pattern, $GLOBALS['TSFE']->content, $cssTags);
		//t3lib_div::debug($cssTags);
		if (!count($cssTags[0])) {
			return;
		}

		// remove any css code inside the output content
		$GLOBALS['TSFE']->content = preg_replace(
			$pattern,
			'',
			$GLOBALS['TSFE']->content, count($cssTags[0])
		);

		// parse matches
		$amountOfResults = count($cssTags[0]);
		for ($i = 0; $i < $amountOfResults; ++$i) {
			// get media attribute (screen as default if it's empty)
			$media = ($cssTags[3][$i] === '') ? 'screen' : $cssTags[3][$i];
			$media = implode(',', array_map('trim', explode(',', $media)));

			// get rel attribute (stylesheet as default if it's empty)
			$relation = ($cssTags[5][$i] === '') ? 'stylesheet' : $cssTags[5][$i];

			// get source attribute
			$source = $cssTags[4][$i];

			// styles which are added inside the document must be parsed again
			// to fetch the pure css code
			if ($cssTags[1][$i] === 'style') {
				$cssContent = array();
				$pattern = '/' .
					'<style.*?>' .					// This expression removes the opening style tag
					'(?:.*?\/\*<!\[CDATA\[\*\/)?' .	// and the optionally prefixed CDATA string.
					'\s*(.*?)' .					// We save the pure css content,
					'(?:\s*\/\*\]\]>\*\/)?' .		// remove the possible closing CDATA string
					'\s*<\/style>' .				// and closing style tag
					'/is';
				preg_match_all($pattern, $cssTags[0][$i], $cssContent);
				//t3lib_div::debug($cssContent);

				// we doesn't need to continue if it was an empty style tag
				if ($cssContent[1][0] === '') {
					continue;
				}

				// save the content into a temporary file
				$source = tempnam($this->tempDirectories['temp'], 'scriptmerger-temp-') . '.css';
				t3lib_div::writeFile($source, $cssContent[1][0]);

				// try to resolve any @import occurences
				$content = Minify_ImportProcessor::process($source);
				$this->css[$relation][$media][$i]['minify-ignore'] = false;
				$this->css[$relation][$media][$i]['compress-ignore'] = false;
				$this->css[$relation][$media][$i]['merge-ignore'] = false;
				$this->css[$relation][$media][$i]['file'] = $source;
				$this->css[$relation][$media][$i]['content'] = $content;
			} else {
				// try to fetch the content of the css file
				$content = '';
				$file = PATH_site . str_replace($GLOBALS['TSFE']->absRefPrefix, '', $source);
				if (file_exists($file)) {
					$content = Minify_ImportProcessor::process($file);
				} else {
					$content = Minify_ImportProcessor::process($source);
				}

				// ignore this file if the content could not be fetched
				if ($content == '') {
					$this->css[$relation][$media][$i]['minify-ignore'] = true;
					$this->css[$relation][$media][$i]['compress-ignore'] = true;
					$this->css[$relation][$media][$i]['merge-ignore'] = true;
					$this->css[$relation][$media][$i]['source'] = $source;
					$this->css[$relation][$media][$i]['content'] = '';
					continue;
				}

				// check if the file should be ignored for some processes
				$this->css[$relation][$media][$i]['minify-ignore'] = false;
				$this->css[$relation][$media][$i]['compress-ignore'] = false;
				$this->css[$relation][$media][$i]['merge-ignore'] = false;

				if ($this->extConfig['css.']['minify.']['ignore'] !== '') {
					if (preg_match($this->extConfig['css.']['minify.']['ignore'], $source)) {
						$this->css[$relation][$media][$i]['minify-ignore'] = true;
					}
				}

				if ($this->extConfig['css.']['compress.']['ignore'] !== '') {
					if (preg_match($this->extConfig['css.']['compress.']['ignore'], $source)) {
						$this->css[$relation][$media][$i]['compress-ignore'] = true;
					}
				}

				if ($this->extConfig['css.']['merge.']['ignore'] !== '') {
					if (preg_match($this->extConfig['css.']['merge.']['ignore'], $source)) {
						$this->css[$relation][$media][$i]['merge-ignore'] = true;
					}
				}

				// set the css file with it's content
				$this->css[$relation][$media][$i]['source'] = $source;
				$this->css[$relation][$media][$i]['content'] = $content;
			}

			// get basename for later usage
			// basename without file prefix and prefixed hash of the dirname
			$filename = basename($source);
			$hash = md5(dirname($source));
			$this->css[$relation][$media][$i]['basename'] =
				substr($filename, 0, strrpos($filename, '.')) . '-' . $hash;
		}
	}

	/**
	 * This method parses the output content and saves any found javascript files or inline code
	 * into the "javascript" class property. The output content is cleaned up of the found results.
	 *
	 * @return array js files
	 */
	protected function getJavascriptFiles() {
		// fetch the head content
		$head = array();
		$pattern = '/<head>.*<\/head>/is';
		preg_match($pattern, $GLOBALS['TSFE']->content, $head);
		$head = $head[0];

		// parse all available css code inside script tags
		$headJavascriptTags = array();
		$pattern = '/' .
			'<script' .			// This expression includes any script nodes
				'(?=.+?(?:type="(text\/javascript)"|>))' .	// which has the type text/javascript.
				'(?=.+?(?:src="(.*?)"|>))' .				// It fetches the src attribute.
			'.+?\1.+?' .		// Finally we finish the parsing of the opening tag
			'<\/script>\s*' .	// until the possible closing tag.
			'/is';
		preg_match_all($pattern, $head, $headJavascriptTags);
		//t3lib_div::debug($headJavascriptTags);

		// remove any css code inside the output content
		if (count($headJavascriptTags[0])) {
			$head = preg_replace($pattern, '', $head, count($headJavascriptTags[0]));
		}

		// replace head with new one
		$pattern = '/<head>.*<\/head>/is';
		$GLOBALS['TSFE']->content = preg_replace(
			$pattern,
			$head,
			$GLOBALS['TSFE']->content
		);

		// fetch the body content
		$javascriptTags = array();
		if ($this->extConfig['javascript.']['parseBody'] === '1') {
			$body = array();
			$pattern = '/<body>.*<\/body>/is';
			preg_match($pattern, $GLOBALS['TSFE']->content, $body);
			$body = $body[0];

			// parse all available css code inside script tags
			$bodyJavascriptTags = array();
			$pattern = '/' .
				'<script' .			// This expression includes any script nodes
					'(?=.+?(?:type="(text\/javascript)"|>))' .	// which has the type text/javascript.
					'(?=.+?(?:src="(.*?)"|>))' .				// It fetches the src attribute.
				'.+?\1.+?' .		// Finally we finish the parsing of the opening tag
				'<\/script>\s*' .	// until the possible closing tag.
				'/is';
			preg_match_all($pattern, $body, $bodyJavascriptTags);
			//t3lib_div::debug($bodyJavascriptTags);

			// remove any css code inside the output content
			// we leave markers in the form ###100### at the original places to write them
			// back here; it's started by 100
			if (count($bodyJavascriptTags[0])) {
				$function = create_function(
					'',
					'static $i = 100; return \'###\' . $i++ . \'###\';'
				);

				$body = preg_replace_callback(
					$pattern,
					$function,
					$body,
					count($bodyJavascriptTags[0])
				);
			}

			// replace body with new one
			$pattern = '/<body>.*<\/body>/is';
			$GLOBALS['TSFE']->content = preg_replace(
				$pattern,
				$body,
				$GLOBALS['TSFE']->content
			);

			// merge results from body and head
			// starting index at 100 for body scripts
			$amountOfResults = count($bodyJavascriptTags[0]);
			for ($i = 0; $i < $amountOfResults; ++$i) {
				$headJavascriptTags[0][$i + 100] = $bodyJavascriptTags[0][$i];
				$headJavascriptTags[1][$i + 100] = $bodyJavascriptTags[1][$i];
				$headJavascriptTags[2][$i + 100] = $bodyJavascriptTags[2][$i];
			}
		}

		// if there are no results, stop parsing now!
		$javascriptTags = $headJavascriptTags;
		//t3lib_div::debug($javascriptTags);
		if (!count($javascriptTags[0])) {
			return;
		}

		// parse matches
		$amountOfResults = count($javascriptTags[0]);
		$indexes = array_keys($javascriptTags[0]);
		for ($i = 0; $i < $amountOfResults; ++$i) {
			// get source attribute
			$index = $indexes[$i];
			$source = $javascriptTags[2][$index];

			// styles which are added inside the document must be parsed again
			// to fetch the pure css code
			if ($source === '') {
				$javascriptContent = array();
				$pattern = '/' .
					'<script.*?>' .					// The expression removes the opening script tag
					'(?:.*?\/\*<!\[CDATA\[\*\/)?' .	// and the optionally prefixed CDATA string.
					'(?:.*?<!--)?' .				// senseless <!-- construct
					'\s*(.*?)' .					// We save the pure css content,
					'(?:\s*\/\/\s*-->)?' .			// senseless <!-- construct
					'(?:\s*\/\*\]\]>\*\/)?' .		// remove the possible closing CDATA string
					'\s*<\/script>' .				// and closing script tag
					'/is';
				preg_match_all($pattern, $javascriptTags[0][$index], $javascriptContent);
				//t3lib_div::debug($javascriptContent);

				// we doesn't need to continue if it was an empty style tag
				if ($javascriptContent[1][0] === '') {
					continue;
				}

				// save the content into a temporary file
				$source = tempnam($this->tempDirectories['temp'], 'scriptmerger-temp-') . '.js';
				t3lib_div::writeFile($source, $cssContent[1][0]);

				// try to resolve any @import occurences
				$this->javascript[$index]['minify-ignore'] = false;
				$this->javascript[$index]['compress-ignore'] = false;
				$this->javascript[$index]['merge-ignore'] = false;
				$this->javascript[$index]['file'] = $source;
				$this->javascript[$index]['content'] = $javascriptContent[1][0];

			} else {
				// try to fetch the content of the css file
				$content = '';
				$file = PATH_site . str_replace($GLOBALS['TSFE']->absRefPrefix, '', $source);
				if (file_exists($file)) {
					$content = file_get_contents($file);
				} else {
					$content = t3lib_div::getURL($source);
				}

				// ignore this file if the content could not be fetched
				if ($content == '') {
					$this->javascript[$index]['minify-ignore'] = true;
					$this->javascript[$index]['compress-ignore'] = true;
					$this->javascript[$index]['merge-ignore'] = true;
					$this->javascript[$index]['file'] = $source;
					$this->javascript[$index]['content'] = '';
					continue;
				}

				// check if the file should be ignored for some processes
				$this->javascript[$index]['minify-ignore'] = false;
				$this->javascript[$index]['compress-ignore'] = false;
				$this->javascript[$index]['merge-ignore'] = false;

				if ($this->extConfig['javascript.']['minify.']['ignore'] !== '') {
					if (preg_match($this->extConfig['javascript.']['minify.']['ignore'], $source)) {
						$this->javascript[$index]['minify-ignore'] = true;
					}
				}

				if ($this->extConfig['javascript.']['compress.']['ignore'] !== '') {
					if (preg_match($this->extConfig['javascript.']['compress.']['ignore'], $source)) {
						$this->javascript[$index]['compress-ignore'] = true;
					}
				}

				if ($this->extConfig['javascript.']['merge.']['ignore'] !== '') {
					if (preg_match($this->extConfig['javascript.']['merge.']['ignore'], $source)) {
						$this->javascript[$index]['merge-ignore'] = true;
					}
				}

				// set the javascript file with it's content
				$this->javascript[$index]['file'] = $source;
				$this->javascript[$index]['content'] = $content;
			}

			// get basename for later usage
			// basename without file prefix and prefixed hash of the dirname
			$filename = basename($source);
			$hash = md5(dirname($source));
			$this->javascript[$index]['basename'] = substr(
				$filename,
				0,
				strrpos($filename, '.')
			) . '-' . $hash;
		}
	}

	/**
	 * This method minifies a css file. It's based upon the Minify_CSS class
	 * of the project minify.
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function minifyCSSfile(&$properties) {
		// get new filename
		$newFile = $this->tempDirectories['minified'] .
			$properties['basename'] . '.min.css';

		// minify content
		$properties['content'] = Minify_CSS::minify($properties['content']);

		// save content inside the new file
		t3lib_div::writeFile($newFile, $properties['content']);

		// save new part of the basename
		$properties['basename'] .= '.min';

		return $newFile;
	}
	
	/**
	 * This method minifies a javascript file. It's based upon the JSMin+ class
	 * of the project minify.
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function minifyJavascriptFile(&$properties) {
		// get new filename
		$newFile = $this->tempDirectories['minified'] .
			$properties['basename'] . '.min.js';

		// minify content
		$properties['content'] = JSMinPlus::minify($properties['content']);

		// save content inside the new file
		t3lib_div::writeFile($newFile, $properties['content']);

		// save new part of the basename
		$properties['basename'] .= '.min';

		return $newFile;
	}

	/**
	 * This method compresses a css file.
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function compressCSSfile(&$properties) {
		// get new filename
		$newFile = $this->tempDirectories['compressed'] .
			$properties['basename'] . '.gz.css';

		// compress content
		$properties['content'] = gzencode($properties['content'], 9);

		// save content inside the new file
		t3lib_div::writeFile($newFile, $properties['content']);

		// save new part of the basename
		$properties['basename'] .= '.gz';

		return $newFile;
	}
	
	/**
	 * This method compresses a javascript file.
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function compressJavascriptFile(&$properties) {
		// get new filename
		$newFile = $this->tempDirectories['compressed'] .
			$properties['basename'] . '.gz.js';

		// compress content
		$properties['content'] = gzencode($properties['content'], 9);

		// save content inside the new file
		t3lib_div::writeFile($newFile, $properties['content']);

		// save new part of the basename
		$properties['basename'] .= '.gz';

		return $newFile;
	}
	
	/**
	 * This method writes the css back to the document.
	 *
	 * @return void
	 */
	protected function writeCSStoDocument() {
		// prepare pattern
		$pattern = '/<\/head>/is';

		// write all files back to the document
		ksort($this->css);
		foreach ($this->css as $relation => $cssByRelation) {
			foreach ($cssByRelation as $media => $cssByMedia) {
				foreach ($cssByMedia as $index => $cssProperties) {
					$file = $cssProperties['file'];

					// normal file or http link?
					if (file_exists($file)) {
						$file = $GLOBALS['TSFE']->absRefPrefix .
							str_replace(PATH_site, '', $file);
					}

					// build css link
					$content = "\t" . '<link rel="' . $relation . '" type="text/css" ' .
						'media="' . $media . '" href="' . $file . '" />' . "\n";

					// add content right before the closing head tag
					$GLOBALS['TSFE']->content = preg_replace(
						$pattern,
						$content . '\0',
						$GLOBALS['TSFE']->content
					);
				}
			}
		}
	}

	/**
	 * This method writes the javascript back to the document.
	 *
	 * @return void
	 */
	protected function writeJavascriptToDocument() {
		// prepare pattern
		$pattern = '/<\/head>/is';
		if ($this->extConfig['javascript.']['addBeforeBody'] === '1') {
			$pattern = '/<\/body>/is';
		}

		// write all files back to the document
		ksort($this->javascript);
		foreach ($this->javascript as $index => $javascriptProperties) {
			$file = $javascriptProperties['file'];

			// normal file or http link?
			if (file_exists($file)) {
				$file = $GLOBALS['TSFE']->absRefPrefix .
					str_replace(PATH_site, '', $file);
			}

			// build javascript script link
			$content = "\t" . '<script type="text/javascript" src="' . $file . '"></script>' . "\n";

			// add body script backt to their original place if they were ignored
			if ($index >= 100) {
				$GLOBALS['TSFE']->content = str_replace(
					'###' . $index . '###',
					$content,
					$GLOBALS['TSFE']->content
				);
				continue;
			}

			// add content right before the closing head tag
			$GLOBALS['TSFE']->content = preg_replace(
				$pattern,
				$content . '\0',
				$GLOBALS['TSFE']->content
			);
		}

		// remove all empty body markers above 100
		if ($this->extConfig['javascript.']['parseBody'] === '1') {
			$pattern = '/###[0-9]{3}###/is';
			$GLOBALS['TSFE']->content = preg_replace($pattern, '', $GLOBALS['TSFE']->content);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scriptmerger/class.tx_scriptmerger.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scriptmerger/class.tx_scriptmerger.php']);
}

?>
