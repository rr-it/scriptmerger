<?php

namespace SGalinski\Scriptmerger;

/***************************************************************
 *  Copyright notice
 *
 *  (c) Stefan Galinski <stefan@sgalinski.de>
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

use JShrink\Minifier;
use SGalinski\Scriptmerger\Exceptions\BrokenIntegrityException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * This class contains the parsing and replacing functionality for javascript files
 */
class ScriptmergerJavascript extends ScriptmergerBase {
	/**
	 * holds the javascript code
	 *
	 * Structure:
	 * - $file
	 *   |-content => string
	 *   |-basename => string (base name of $file without file prefix)
	 *   |-minify-ignore => bool
	 *   |-merge-ignore => bool
	 *
	 * @var array
	 */
	protected $javascript = [];

	/**
	 * Controller for the processing of the javascript files.
	 *
	 * @return void
	 */
	public function process() {
		// fetch all javascript content
		$this->getFiles();

		// minify, compress and merging
		$gzCompressExists = \function_exists('gzcompress');
		foreach ($this->javascript as $section => $javascriptBySection) {
			$mergedContent = '';
			$positionOfMergedFile = NULL;
			foreach ($javascriptBySection as $index => $javascriptProperties) {
				$newFile = '';

				// file should be minified
				if (!$javascriptProperties['minify-ignore'] &&
					$this->configuration['javascript.']['minify.']['enable'] === '1'
				) {
					$newFile = $this->minifyFile($javascriptProperties);
				}

				// file should be merged
				if (!$javascriptProperties['merge-ignore'] &&
					$this->configuration['javascript.']['merge.']['enable'] === '1'
				) {
					if ($positionOfMergedFile === NULL) {
						$positionOfMergedFile = $javascriptProperties['position-key'];
					}

					$mergedContent .= $javascriptProperties['content'] . LF;
					unset($this->javascript[$section][$index]);
					continue;
				}

				// file should be compressed instead?
				if ($gzCompressExists && !$javascriptProperties['compress-ignore'] &&
					$this->configuration['javascript.']['compress.']['enable'] === '1'
				) {
					$newFile = $this->compressFile($javascriptProperties);
				}

				// minification or compression was used
				if ($newFile !== '') {
					$this->javascript[$section][$index]['file'] = $newFile;
					$this->javascript[$section][$index]['content'] =
						$javascriptProperties['content'];
					$this->javascript[$section][$index]['basename'] =
						$javascriptProperties['basename'];
				}
			}

			// save merged content inside a new file
			if ($mergedContent !== '' && $this->configuration['javascript.']['merge.']['enable'] === '1') {
				$properties = [
					'content' => $mergedContent,
					'basename' => $section . '-' . md5($mergedContent) . '.merged'
				];

				// write merged file in any case
				$newFile = $this->tempDirectories['merged'] . $properties['basename'] . '.js';
				if (!file_exists($newFile)) {
					$this->writeFile($newFile, $properties['content']);
				}

				// file should be compressed
				if ($gzCompressExists && $this->configuration['javascript.']['compress.']['enable'] === '1') {
					$newFile = $this->compressFile($properties);
				}

				$integrityType = 'sha512';
				// add new entry
				$this->javascript[$section][] = [
					'file' => $newFile,
					'content' => $properties['content'],
					'basename' => $properties['basename'],
					'position-key' => $positionOfMergedFile,
					'integrity' => $integrityType . '-' . $this->calculateIntegrity($properties['content'])
				];
			}
		}

		// write javascript content back to the document
		$this->writeToDocument();
	}

	/**
	 * This method parses the output content and saves any found javascript files or inline code
	 * into the "javascript" class property. The output content is cleaned up of the found results.
	 */
	protected function getFiles() {
		$javascriptTags = [
			'head' => [],
			'body' => []
		];

		// create search pattern
		$searchScriptsPattern = '/' .
			'<script' . // This expression includes any script nodes.
			'(?=.+?(?:src="(.*?)"|>))' . // It fetches the src attribute.
			'(?=.+?(?:data-ignore=["\'](.*?)["\']|>))' . // and the data-ignore attribute of the tag.
			'(?=.+?(?:integrity="(.*?)"|>))' . // and the integrity attribute
			'[^>]*?>' . // Finally we finish the parsing of the opening tag
			'.*?<\/script>\s*' . // until the closing tag.
			'/is';

		// filter pattern for the inDoc scripts (fetches the content)
		$filterInDocumentPattern = '/' .
			'<script' . // This expression includes any script nodes.
			'(?=.+?(?:type="(.*?)"|>))' . // It fetches the type attribute.
			'(?=.+?(?:data-ignore=["\'](.*?)["\']|>))' . // and the data-ignore attribute of the tag.
			'[^>]*?>' . // Finally we finish the parsing of the opening tag
			'(?:.*?\/\*<!\[CDATA\[\*\/)?' . // and the optionally prefixed CDATA string.
			'(?:.*?<!--)?' . // senseless <!-- construct
			'\s*(.*?)' . // We save the pure js content,
			'(?:\s*\/\/\s*-->)?' . // senseless <!-- construct
			'(?:\s*\/\*\]\]>\*\/)?' . // remove the possible closing CDATA string
			'\s*<\/script>' . // and closing script tag
			'/is';

		// parse scripts in the head
		$head = [];
		preg_match('/<head>.+?<\/head>/is', $GLOBALS['TSFE']->content, $head);
		$head = $oldHead = $head[0];

		preg_match_all($searchScriptsPattern, $head, $javascriptTags['head']);
		$amountOfScriptTags = \count($javascriptTags['head'][0]);
		if ($amountOfScriptTags) {
			$function = function () {
				static $i = 0;
				return '###MERGER-head' . $i++ . 'MERGER###';
			};

			$head = preg_replace_callback($searchScriptsPattern, $function, $head, $amountOfScriptTags);
			$GLOBALS['TSFE']->content = str_replace($oldHead, $head, $GLOBALS['TSFE']->content);
		}

		// parse scripts in the body
		if ($this->configuration['javascript.']['parseBody'] === '1') {
			$body = [];
			preg_match('/<body.*>.+?<\/body>/is', $GLOBALS['TSFE']->content, $body);
			$body = $oldBody = $body[0];

			preg_match_all($searchScriptsPattern, $body, $javascriptTags['body']);
			$amountOfScriptTags = \count($javascriptTags['body'][0]);
			if ($amountOfScriptTags) {
				$function = function () {
					static $i = 0;
					return '###MERGER-body' . $i++ . 'MERGER###';
				};

				$body = preg_replace_callback($searchScriptsPattern, $function, $body, $amountOfScriptTags);
				$GLOBALS['TSFE']->content = str_replace($oldBody, $body, $GLOBALS['TSFE']->content);
			}
		}

		foreach ($javascriptTags as $section => $results) {
			$amountOfResults = isset($results[0]) ? \count($results[0]) : 0;
			for ($i = 0; $i < $amountOfResults; ++$i) {
				// get source attribute
				$source = trim($results[1][$i]);
				$isSourceFromMainAttribute = FALSE;
				if ($source !== '') {
					preg_match('/^<script([^>]*)>/', trim($results[0][$i]), $scriptAttribute);
					$isSourceFromMainAttribute = (strpos($scriptAttribute[1], $source) !== FALSE);
				}
				$ignoreDataFlagSet = (int) $results[2][$i];

				// add basic entry
				$this->javascript[$section][$i]['minify-ignore'] = FALSE;
				$this->javascript[$section][$i]['compress-ignore'] = FALSE;
				$this->javascript[$section][$i]['merge-ignore'] = FALSE;
				$this->javascript[$section][$i]['addInDocument'] = FALSE;
				$this->javascript[$section][$i]['useOriginalCodeLine'] = FALSE;
				$this->javascript[$section][$i]['file'] = $source;
				$this->javascript[$section][$i]['content'] = '';
				$this->javascript[$section][$i]['basename'] = '';
				$this->javascript[$section][$i]['position-key'] = $i;
				$this->javascript[$section][$i]['original'] = $results[0][$i];

				if ($isSourceFromMainAttribute) {
					// try to fetch the content of the file
					$localFile = $source;
					if ($GLOBALS['TSFE']->absRefPrefix !== '' &&
						\strpos($localFile, $GLOBALS['TSFE']->absRefPrefix) === 0
					) {
						$localFile = \substr($localFile, \strlen($GLOBALS['TSFE']->absRefPrefix) - 1);
					}

					if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0.0', '<')) {
						$pathSite = PATH_site;
					} else {
						$pathSite = Environment::getPublicPath() . '/';
					}

					$localFile = $pathSite . $localFile;
					if (\file_exists($localFile)) {
						$file = $localFile;
					} else {
						$file = $source;
					}
					$integrity = $results[3][$i];
					try {
						$content = $this->getFile($file, TRUE, $integrity);
					} catch (BrokenIntegrityException $exception) {
						// The file integrity is broken, this could mean, that the script target got hacked and is not
						// safe anymore. We need to abort merging of this script and report the issue
						$this->logger->warning(
							$exception->getMessage(), [
								'file' => $file,
								'integrity' => $integrity,
								'tag' => $results[0][$i]
							]
						);
						$content = '';
					}

					// ignore this file if the content could not be fetched
					if ($ignoreDataFlagSet || trim($content) === '') {
						$this->javascript[$section][$i]['minify-ignore'] = TRUE;
						$this->javascript[$section][$i]['compress-ignore'] = TRUE;
						$this->javascript[$section][$i]['merge-ignore'] = TRUE;
						$this->javascript[$section][$i]['useOriginalCodeLine'] = TRUE;
						continue;
					}

					// check if the file should be ignored for some processes
					$amountOfIgnores = 0;
					if ($this->configuration['javascript.']['minify.']['ignore'] !== '' &&
						preg_match($this->configuration['javascript.']['minify.']['ignore'], $source)
					) {
						$this->javascript[$section][$i]['minify-ignore'] = TRUE;
						++$amountOfIgnores;
					}

					if ($this->configuration['javascript.']['compress.']['ignore'] !== '' &&
						preg_match($this->configuration['javascript.']['compress.']['ignore'], $source)
					) {
						$this->javascript[$section][$i]['compress-ignore'] = TRUE;
						++$amountOfIgnores;
					}

					if ($this->configuration['javascript.']['merge.']['ignore'] !== '' &&
						preg_match($this->configuration['javascript.']['merge.']['ignore'], $source)
					) {
						$this->javascript[$section][$i]['merge-ignore'] = TRUE;
						++$amountOfIgnores;
					}

					if ($amountOfIgnores === 3) {
						$this->javascript[$section][$i]['useOriginalCodeLine'] = TRUE;
					}

					// set the javascript file with it's content
					$this->javascript[$section][$i]['file'] = $source;
					$this->javascript[$section][$i]['content'] = $content;

					// get base name for later usage
					// base name without file prefix and prefixed hash of the content
					$filename = basename($source);
					$hash = md5($content);
					$this->javascript[$section][$i]['basename'] =
						substr($filename, 0, strrpos($filename, '.')) . '-' . $hash;

				} else {
					// scripts which are added inside the document must be parsed again
					// to fetch the pure js code
					$javascriptContent = [];
					preg_match_all($filterInDocumentPattern, $results[0][$i], $javascriptContent);

					$scriptTagType = $javascriptContent[1][0];
					$scriptTagIgnore = (bool) $javascriptContent[2][0];
					$scriptTagContent = $javascriptContent[3][0];

					// ignore this tag if it is marked with data-ignore
					// ignore also if the type is specified as something different than javascript
					// if not type is declared at all, assume javascript to be the correct type
					if ($scriptTagIgnore || ($scriptTagType !== '' && $scriptTagType !== 'text/javascript')) {
						$this->javascript[$section][$i]['minify-ignore'] = TRUE;
						$this->javascript[$section][$i]['compress-ignore'] = TRUE;
						$this->javascript[$section][$i]['merge-ignore'] = TRUE;
						$this->javascript[$section][$i]['useOriginalCodeLine'] = TRUE;
					}

					// we don't need to continue if it was an empty script tag
					if ($scriptTagContent === '' && ($scriptTagType === 'text/javascript' || $scriptTagType === '')) {
						unset($this->javascript[$section][$i]);
						continue;
					}

					$doNotRemoveinDocInBody =
						($this->configuration['javascript.']['doNotRemoveInDocInBody'] === '1' && $section === 'body');
					$doNotRemoveinDocInHead =
						($this->configuration['javascript.']['doNotRemoveInDocInHead'] === '1' && $section === 'head');
					if ($doNotRemoveinDocInHead || $doNotRemoveinDocInBody || $ignoreDataFlagSet) {
						$this->javascript[$section][$i]['minify-ignore'] = TRUE;
						$this->javascript[$section][$i]['compress-ignore'] = TRUE;
						$this->javascript[$section][$i]['merge-ignore'] = TRUE;
						$this->javascript[$section][$i]['useOriginalCodeLine'] = TRUE;
						$this->javascript[$section][$i]['addInDocument'] = TRUE;
						$this->javascript[$section][$i]['content'] = $scriptTagContent;
						continue;
					}

					// save the content into a temporary file
					$hash = md5($scriptTagContent);
					$source = $this->tempDirectories['temp'] . 'inDocument-' . $hash;

					if (!file_exists($source . '.js')) {
						$this->writeFile($source . '.js', $scriptTagContent);
					}

					$this->javascript[$section][$i]['file'] = $source . '.js';
					$this->javascript[$section][$i]['content'] = $scriptTagContent;
					$this->javascript[$section][$i]['basename'] = basename($source);
				}
			}
		}
	}

	/**
	 * This method minifies a javascript file. It's based upon the JSMin+ class
	 * of the project minify. Alternatively the old JSMin class can be used, but it's
	 * definitely not the preferred solution!
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function minifyFile(&$properties): string {
		// stop further processing if the file already exists
		$newFile = $this->tempDirectories['minified'] . $properties['basename'] . '.min.js';
		if (file_exists($newFile)) {
			$properties['basename'] .= '.min';
			$properties['content'] = file_get_contents($newFile);
			return $newFile;
		}

		// check for conditional compilation code to fix an issue with jsmin+
		$hasConditionalCompilation = FALSE;
		if ($this->configuration['javascript.']['minify.']['useJSMinPlus'] === '1') {
			$hasConditionalCompilation = preg_match('/\/\*@cc_on/i', $properties['content']);
		}

		// minify content (the ending semicolon must be added to prevent minimisation bugs)
		$hasErrors = FALSE;
		$minifiedContent = '';
		try {
			if (!$hasConditionalCompilation && $this->configuration['javascript.']['minify.']['useJShrink'] === '1') {
				/** @noinspection ClassConstantCanBeUsedInspection */
				if (!class_exists('JShrink\Minifier', FALSE)) {
					require_once ExtensionManagementUtility::extPath('scriptmerger') . 'Resources/JShrink/Minifier.php';
				}

				$minifiedContent = Minifier::minify($properties['content']);
			} elseif (!$hasConditionalCompilation && $this->configuration['javascript.']['minify.']['useJSMinPlus'] === '1') {
				if (!class_exists('JSMinPlus', FALSE)) {
					require_once ExtensionManagementUtility::extPath('scriptmerger') . 'Resources/jsminplus.php';
				}

				$minifiedContent = \JSMinPlus::minify($properties['content']);

			} else {
				if (!class_exists('JSMin', FALSE)) {
					require_once ExtensionManagementUtility::extPath('scriptmerger') . 'Resources/jsmin.php';
				}

				/** @noinspection PhpUndefinedClassInspection */
				$minifiedContent = \JSMin::minify($properties['content']);
			}
		} catch (\Exception $exception) {
			$hasErrors = TRUE;
		}

		// check if the minified content has more than two characters or more than 50 lines and no errors occurred
		if (!$hasErrors && (\strlen($minifiedContent) > 2 || substr_count($minifiedContent, LF) + 1 > 50)) {
			$properties['content'] = $minifiedContent . ';';
		} else {
			$message = 'This javascript file could not be minified: "' . $properties['file'] . '"! ' .
				'You should exclude it from the minification process!';
			GeneralUtility::sysLog($message, 'scriptmerger', GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}

		$this->writeFile($newFile, $properties['content']);
		$properties['basename'] .= '.min';

		return $newFile;
	}

	/**
	 * This method compresses a javascript file.
	 *
	 * @param array $properties properties of an entry (copy-by-reference is used!)
	 * @return string new filename
	 */
	protected function compressFile(&$properties): string {
		$newFile = $this->tempDirectories['compressed'] . $properties['basename'] . '.gz.js';
		if (file_exists($newFile)) {
			return $newFile;
		}

		$this->writeFile($newFile, gzencode($properties['content'], 5));

		return $newFile;
	}

	/**
	 * This method writes the javascript back to the document.
	 *
	 * @return void
	 */
	protected function writeToDocument() {
		$shouldBeAddedInDoc = $this->configuration['javascript.']['addContentInDocument'] === '1';
		$asyncLoading = (bool) $this->configuration['javascript.']['asyncLoading'];
		$deferLoadingInHead = (bool) $this->configuration['javascript.']['deferLoadingInHead'];
		if ($deferLoadingInHead) {
			$asyncLoading = FALSE;
		}
		foreach ($this->javascript as $section => $javascriptBySection) {
			ksort($javascriptBySection);
			if (!\is_array($javascriptBySection)) {
				continue;
			}

			// addBeforeBody was deprecated in version 4.0.0 and can be removed later on
			// Use $pattern = '/(...)/i' to make preg_split(..., PREG_SPLIT_DELIM_CAPTURE) work.
			$pattern = '';
			if ($section === 'body' || $this->configuration['javascript.']['addBeforeBody'] === '1') {
				if ($deferLoadingInHead) {
					$pattern = '/(' . preg_quote($this->configuration['javascript.']['mergedHeadFilePosition'], '/') . ')/i';
				} else {
					$pattern = '/(' . preg_quote($this->configuration['javascript.']['mergedBodyFilePosition'], '/') . ')/i';
				}
			} elseif (trim($this->configuration['javascript.']['mergedHeadFilePosition']) !== '') {
				$pattern = '/(' . preg_quote($this->configuration['javascript.']['mergedHeadFilePosition'], '/') . ')/i';
			}

			foreach ($javascriptBySection as $javascriptProperties) {
				if ($javascriptProperties['useOriginalCodeLine']) {
					$content = $javascriptProperties['original'];
				} elseif ($shouldBeAddedInDoc || $javascriptProperties['addInDocument']) {
					$content = "\t" .
						'<script type="text/javascript">' . LF .
						"\t" . '/* <![CDATA[ */' . LF .
						"\t" . $javascriptProperties['content'] . LF .
						"\t" . '/* ]]> */' . LF .
						"\t" . '</script>' . LF;
				} else {
					$file = $javascriptProperties['file'];
					if (file_exists($file)) {
						if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0.0', '<')) {
							$pathSite = PATH_site;
						} else {
							$pathSite = Environment::getPublicPath() . '/';
						}

						$file = $GLOBALS['TSFE']->absRefPrefix .
							($pathSite === '/' ? $file : str_replace($pathSite, '', $file));
					}
					$content = "\t" .
						'<script ' . ($asyncLoading ? 'async ' : '') . ($deferLoadingInHead ? 'defer ' : '')
						. 'type="text/javascript" '
						. 'src="' . $file . '" '
						. 'integrity="' . $javascriptProperties['integrity'] . '" '
						. 'crossorigin="anonymous"></script>' . LF;
				}

				if ($pattern === '' || $javascriptProperties['merge-ignore']) {
					// add body scripts back to their original place if they were ignored
					$GLOBALS['TSFE']->content = str_replace(
						'###MERGER-' . $section . $javascriptProperties['position-key'] . 'MERGER###',
						$content,
						$GLOBALS['TSFE']->content
					);
				} else {
					// If 'addInDocument'/'addContentInDocument' is set, $content may contain strings like '$1' from
					// inline javascript.
					//
					// Can not use preg_replace($pattern, replacement: $content) here. In the replacement-parameter
					// every reference of the form '$n' will be replaced.
					// @see https://www.php.net/manual/en/function.preg-replace.php Documentation of preg_replace.
					$aTSFEContent = preg_split($pattern, $GLOBALS['TSFE']->content, 2, PREG_SPLIT_DELIM_CAPTURE);
					$GLOBALS['TSFE']->content = array_shift($aTSFEContent) . $content . array_shift($aTSFEContent) . array_shift($aTSFEContent);
				}
			}
		}

		// remove any empty markers
		$pattern = '/###MERGER-(head|body)[0-9]*?MERGER###/is';
		$GLOBALS['TSFE']->content = preg_replace($pattern, '', $GLOBALS['TSFE']->content);
	}
}
