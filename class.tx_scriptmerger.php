<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2007 Stefan Galinski <Stefan.Galinski@frm2.tum.de>
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
 * Could be used to merge css and js files. Now only two requests are needed to
 * get the whole external code. Should reduce the initial loading time of
 * a page dramatically.
 *
 * @author Stefan Galinski <stefan.galinski@frm2.tum.de>
 */

/// css tidy inlcusion
require_once('res/csstidy/class.csstidy.php');

/**
 * Could be used to merge css and js files. Now only two requests are needed to
 * get the whole external code. Should reduce the initial loading time of
 * a page dramatically.
 *
 * @author Stefan Galinski <stefan.galinski@frm2.tum.de>
 */
class tx_scriptmerger
{
	/** @var $extConfig array holds the extension configuration */
	var $extConfig = array();

	/** @var $cssFiles array holds all css files */
	var $cssFiles = array();

	/** @var $cssFiles array holds all js files */
	var $jsFiles = array();

	/** @var $cssFiles array holds all ignored js files */
	var $ignoredJsFiles = array();

	/** @var $cssTidy hold the cssTidy object */
	var $cssTidy = null;

	/**
	 * Constructor
	 *
	 * Prepares the extension configuration array!
	 *
	 * @return void
	 */
	function tx_scriptmerger()
	{
		// amount of instances (needed for hook count)
		if (!$instances)
			static $instances = 0;

		// only execute the output hook if dynamic include scripts are available
		if (!$GLOBALS['TSFE']->isINTincScript() && $instances)
			return false;
		++$instances;

		$this->extConfig =
			unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scriptmerger']);
		if (is_array($GLOBALS['TSFE']->tmpl->setup['config.']['tx_scriptmerger.']))
			foreach ($GLOBALS['TSFE']->tmpl->setup['config.']['tx_scriptmerger.'] as $key => $value)
				$this->extConfig[$key] = $value;

		// if the whole css content should be base64 encoded then images needs to be encoded too
		if ($this->extConfig['cssOutputType'] == 'base64')
			$this->extConfig['img2Base64'] = 1;

		// string to array (ignore lists)
		$this->extConfig['jsIgnoreList'] = !empty($this->extConfig['jsIgnoreList']) ?
			explode(',', $this->extConfig['jsIgnoreList']) : array();
		$this->extConfig['jsIgnoreList'][] = '.+\?.+'; // any link with parameters
		$this->extConfig['jsIgnoreList'][] = 'scriptaculous.*.js';
		for ($i = 0; $i < count($this->extConfig['jsIgnoreList']); ++$i)
			$this->extConfig['jsIgnoreList'][$i] =
				addcslashes($this->extConfig['jsIgnoreList'][$i], '/\'');

		$this->extConfig['cssIgnoreList'] = !empty($this->extConfig['cssIgnoreList']) ?
			explode(',', $this->extConfig['cssIgnoreList']) : array();
		for ($i = 0; $i < count($this->extConfig['cssIgnoreList']); ++$i)
			$this->extConfig['cssIgnoreList'][$i] =
				addcslashes($this->extConfig['cssIgnoreList'][$i], '/\'');

		// create cache directory
		if (!is_dir(PATH_site . $this->extConfig['cachePath']))
			t3lib_div::mkdir(PATH_site . $this->extConfig['cachePath']);

		// include js min (only for PHP5)
		if ($this->extConfig['jsmin'])
			require_once('res/jsmin-1.1.0.php');
	}

	/**
	 * Contains the process logic of the whole plugin!
	 *
	 * @return bool true or false
	 */
	function main()
	{

		// disables scriptmerger functionality for some special pages or debug purposes
		if ($this->extConfig['disable'])
			return false;

		// combine css files
		if ($this->extConfig['cssCombine']) {
			$this->cssFiles = $this->getCSSfiles();
			if (count($this->cssFiles))
				$this->setCSSfiles($this->prepCSSfiles());
		}

		// combine js files
		if ($this->extConfig['jsCombine']) {
			$this->jsFiles = $this->getJSfiles();
			if (count($this->jsFiles))
				$this->setJSfiles($this->prepJSfiles());

			if (count($this->ignoredJsFiles))
				foreach ($this->ignoredJsFiles as $file)
					$this->setJSfiles($file);
		}

		return true;
	}

	/**
	 * Writes the css content into the final document
	 *
	 * @param $cssFiles array css files (Structure: $cssFiles[screen|print] = file)
	 * @return boolean true or false
	 */
	function setCSSfiles($cssFiles)
	{
		$add = '';
		foreach ($cssFiles as $media => $file) {
			if ($this->extConfig['cssOutputType'] != 'inDoc') {
				if ($this->extConfig['cssOutputType'] == 'base64')
					$file = 'data:text/css;base64,' .
						base64_encode(file_get_contents(PATH_site . $file));
				$add .= '<link rel="stylesheet" type="text/css" media="' .
					$media . '" href="' . $file . '" />' . "\n";
			} else
				$add .= '<style type="text/css" media="' . $media . '">' . "\n" . '/*<![CDATA[*/' . "\n" .
					file_get_contents(PATH_site . $file) . "\n" . '/*]]>*/' . "\n" . '</style>' . "\n";
		}

		// conditional comments must be below the script!
		$pattern = '/\<(?:\!--\[if.+?\]|\/head)\>/is';
		$replace = $add . '\0';
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, $replace, $GLOBALS['TSFE']->content, 1);

		return true;
	}

	/**
	 * Writes the js content into the final document
	 *
	 * @param $jsFile string js file
	 * @return boolean true or false
	 */
	function setJSfiles($jsFile)
	{
		if ($this->extConfig['jsOutputType'] == 'base64')
			$jsFile = 'data:text/javascript;base64,' .
				base64_encode(file_get_contents(PATH_site . $jsFile));

		$pattern = '/\<!--HD_.+--\>|\<\/head\>/isU';
		$replace = '<script type="text/javascript" src="' . $jsFile . '"></script>' . "\n" . '\0';
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, $replace, $GLOBALS['TSFE']->content, 1);

		return true;
	}

	/**
	 * Prepares the js files for the final document
	 *
	 * @return string final js file
	 */
	function prepJSfiles()
	{
		// write files back to the document
		$fileContent = '';
		foreach($this->jsFiles as $jsFile) {
			$prefix = !t3lib_div::isFirstPartOfStr($jsFile, 'http://') &&
				!t3lib_div::isFirstPartOfStr($jsFile, 'https://') ? PATH_site : '';
			$fileContent .= file_get_contents($prefix . $jsFile) . "\n";
		}
		$finalJSfile = $this->extConfig['cachePath'] . 'javascript_' . md5($fileContent) . '.js';

		// do we need to cache the file?
		if (is_file(PATH_site . $finalJSfile))
			return $finalJSfile;

		// js minification by jsmin
		if ($this->extConfig['jsmin'])
			$fileContent = JSMin::minify($fileContent);

		// write file
		t3lib_div::writeFile(PATH_site . $finalJSfile, $fileContent);

		return $finalJSfile;
	}

	/**
	 * Prepares the css files for the final document
	 *
	 * Includes: Reformatting/Simplification by CSSTidy, Merging and Correction
	 *
	 * @return array final css files (Structure: $cssFiles[screen|print][1-n] = file)
	 */
	function prepCSSfiles()
	{
		// init csstidy
		if ($this->extConfig['csstidy']) {
			$this->cssTidy = new csstidy();
			$this->cssTidy->load_template($this->extConfig['csstidy.']['compression']);
			$this->cssTidy->set_cfg('preserve_css', $this->extConfig['csstidy.']['preserveCSS']);
			$this->cssTidy->set_cfg('remove_last_;', true);
			$this->cssTidy->set_cfg('timestamp', true);
		}

		// write files back to the document
		$finalCSSfiles = array();
		foreach($this->cssFiles as $media => $files)
		{
			// get final filename
			$fileContent = '';
			foreach($files as $cssFile) {
				$prefix = !t3lib_div::isFirstPartOfStr($cssFile, 'http://') &&
					!t3lib_div::isFirstPartOfStr($jsFile, 'https://') ? PATH_site : '';
				$fileContent .= file_get_contents($prefix . $cssFile) . "\n";
			}
			$finalCSSfiles[$media] = $this->extConfig['cachePath'] .
				'stylesheet_' . $media . '_' . md5($fileContent) . '.css';

			// do we need to cache the file?
			if (is_file(PATH_site . $finalCSSfiles[$media]))
				continue;

			// get file contents
			$fileContent = '';
			foreach($files as $cssFile) {
				$prefix = !t3lib_div::isFirstPartOfStr($cssFile, 'http://') &&
					!t3lib_div::isFirstPartOfStr($jsFile, 'https://') ? PATH_site : '';
				$fileContent .= $this->replaceCSSurl(file_get_contents($prefix .
					$cssFile), $cssFile) . "\n";
			}

			// css optimizations by css tidy
			if ($this->extConfig['csstidy']) {
				$this->cssTidy->parse($fileContent);
				$fileContent = $this->cssTidy->print->plain();
				$this->cssTidy->css = array();
				$this->cssTidy->tokens = array();
				$this->cssTidy->import = array();
			}

			// write file
			t3lib_div::writeFile(PATH_site . $finalCSSfiles[$media], $fileContent);
		}

		return $finalCSSfiles;
	}

	/**
	 * Returns an array of css files which are referenced in the head
	 *
	 * The final html content is cleaned up by any occurences of css code!
	 *
	 * @return array css files (Structure: $cssFiles[screen|print][1-n] = file)
	 */
	function getCSSfiles()
	{
		$cssFiles = array();
		$ccomments = array();
		$matches = array();

		// save the conditional comments
		$pattern = '/\<!--\[if.+!\[endif\]--\>/isU';
		preg_match_all($pattern, $GLOBALS['TSFE']->content, $ccomments);
		//t3lib_div::debug($ccomments);
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, '', $GLOBALS['TSFE']->content, count($ccomments[0]));

		// parser (matches and replaces with the same query)
		$pattern =
			'/\<(link|style)' .	// any link or style nodes (node name saved in \1)
			'(?=.+?' .				// anything inside the node; next conditions could have a dynamic order
			'(?=.*?(?:type=["|\'](text\/css)["|\']|\>))' .	// condition (type="text/css" || >) Result: \2
			'(?=.*?(?:media=["|\'](.*?)["|\']|\>))' .			// condition (media || >) Result: \3
			'(?=.*?(?:href=["|\'](.*?)["|\']|\>))' .				// condition (href || >) Result: \4
			').+?\2.*?' .												// \2 holds type condition result (text/css or nothing)
			'(?:\/\>|\<\/style\>)[\c\s' . "\n\r\t" . ']*' .		// until node end is reached
			'/is';

		preg_match_all($pattern, $GLOBALS['TSFE']->content, $matches);
		//t3lib_div::debug($matches);
		if (!count($matches))
			return $cssFiles;
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, '', $GLOBALS['TSFE']->content, count($matches[0]));

		// write conditional comments back to the document
		$pattern = '/\<\/head\>/isU';
		$replace = implode("\n", $ccomments[0]) . "\n" . '\0';
		$GLOBALS['TSFE']->content =
			preg_replace($pattern, $replace, $GLOBALS['TSFE']->content);

		// parse matches
		$length = count($matches[0]);
		$inDoc = array();
		for ($i = 0; $i < $length; ++$i)
		{
			$media = !empty($matches[3][$i]) ? $matches[3][$i] : 'screen';

			// inDoc styles must be parsed to get only the css content
			if ($matches[1][$i] == 'style') {
				$pattern =
					'/\<style.*?\>' .						// begin of style tag
					'(?:.*?\/\*\<!\[CDATA\[\*\/)?' .	// prefixed optional CDATA
					'\s*(.*?)' .								// css code (saved in \1)
					'(?:\s*\/\*\]\]\>\*\/)?' .				// postfixed end of optional CDATA
					'\s*\<\/style\>' .					// end of style tag
					'/is';										// no ungreedy modifier (causes problems)

				preg_match_all($pattern, $matches[0][$i], $content);
				//t3lib_div::debug($content);
				$inDoc[$media] .= $content[1][0] . "\n";

			} elseif ($matches[1][$i] == 'link') {

				// only http and existing files
				if (!t3lib_div::isFirstPartOfStr($matches[4][$i], 'http://') &&
					!t3lib_div::isFirstPartOfStr($matches[4][$i], 'https://') &&
					!is_file(PATH_site . $matches[4][$i]))
					continue;

				// check the ignore list
				$test = false;
				foreach($this->extConfig['cssIgnoreList'] as $ignoreMatch)
					if ($test = preg_match('/' . $ignoreMatch . '/i', basename($matches[4][$i])))
						break;
				if ($test) {
					$this->setCSSfiles(array($media => $matches[4][$i]));
					continue;
				}

				// add files (linked file + possible import files)
				if ($this->extConfig['cssReplaceImport']) {
					$cssFiles[$media] = !is_array($cssFiles[$media]) ? array() : $cssFiles[$media];
					$cssFiles[$media] = array_merge($cssFiles[$media],
						$this->replaceImportRule($matches[4][$i]));
				} else
					$cssFiles[$media][] = $matches[4][$i];
			}
		}

		// write inDoc css into single media files
		if (count($inDoc)) {
			foreach($inDoc as $media => $content) {
				$script = $this->extConfig['cachePath'] . 'stylesheet_inDocCSS_' .
					$media . '_' . md5($content) . '.css';
				if (!is_file(PATH_site . $script))
					t3lib_div::writeFile(PATH_site . $script, $content);

				// add files (inDoc file +possible import files)
				if ($this->extConfig['cssReplaceImport']) {
					$cssFiles[$media] = !is_array($cssFiles[$media]) ? array() : $cssFiles[$media];
					$cssFiles[$media] = array_merge($cssFiles[$media],
							$this->replaceImportRule($script, true));
				} else
					$cssFiles[$media][] = $script;
			}
		}

		return $cssFiles;
	}

	/**
	 * Returns an array of js files which are referenced in the head
	 *
	 * The final html content is cleaned up by any occurences of js code!
	 *
	 * @return array js files
	 */
	function getJSfiles()
	{
		// get head
		$pattern = '/\<head\>.*\<\/head\>/iUs';
		$head = array();
		preg_match($pattern, $GLOBALS['TSFE']->content, $head);
		$head = $head[0];
		$GLOBALS['TSFE']->content = preg_replace($pattern,
			'###HTML_HEAD_SCRIPTMERGER###', $GLOBALS['TSFE']->content);

		// head parser (matches and replaces with the same query)
		$pattern =
			'/\<script' .									// any script nodes
			'(?=.+?' .										// anything inside the node; next conditions could have a dynamic order
			'(?=.*?(?:type=["|\'](text\/javascript)["|\']|\>))' .	// condition (type="text/javascript" || >) Result: \1
			'(?=.*?(?:src=["|\'](.*?)["|\']|\>))' .	// condition (href || >) Result: \2
			').+?\1.*?' .									// \1 holds type condition result (text/javascript or nothing)
			'\<\/script\>[\c\s' . "\n\r\t" . ']*' .	// until node end is reached + possible line ends
			'/is';

		$matches = array();
		preg_match_all($pattern, $head, $matches);
		//t3lib_div::debug($matches);
		if (count($matches))
			$head = preg_replace($pattern, '', $head, count($matches[0]));

		// replace head with new one
		$GLOBALS['TSFE']->content = preg_replace('/###HTML_HEAD_SCRIPTMERGER###/is',
			$head, $GLOBALS['TSFE']->content);

		// parses body
		if ($this->extConfig['jsParseBodyScripts']) {
			// get body
			$pattern = '/\<body\>.*\<\/body\>/is';
			$body = array();
			preg_match($pattern, $GLOBALS['TSFE']->content, $body);
			$body = $body[0];
			$GLOBALS['TSFE']->content = preg_replace($pattern,
				'###HTML_BODY_SCRIPTMERGER###', $GLOBALS['TSFE']->content);

			// body parser (matches and replaces with the same query)
			$pattern =
				'/\<script' .	// any script nodes
				'(?=.+?' .		// anything inside the node; next conditions could have a dynamic order
				'(?=.*?(?:type=["|\'](text\/javascript)["|\']|\>))' .	// condition (type="text/javascript" || >) Result: \1
				'(?=.*?(?:src=["|\'](.+?)["|\'])|\>)' .		// condition (href || >) Result: \2
				').+?(?:\2[^>]*?\1|\1[^>]*?\2).*?' .	// \1 holds type condition result (text/javascript or nothing)
				'\<\/script\>[\c\s' . "\n\r\t" . ']*' .		// until node end is reached + possible line ends
				'/is';

			$matchesBody = array();
			preg_match_all($pattern, $body, $matchesBody);
			//t3lib_div::debug($matchesBody);
			if (count($matchesBody))
				$body = preg_replace($pattern, '', $body, count($matchesBody[0]));
			$matches[0] = array_merge_recursive($matches[0], $matchesBody[0]);
			$matches[1] = array_merge_recursive($matches[1], $matchesBody[1]);
			$matches[2] = array_merge_recursive($matches[2], $matchesBody[2]);

			// replace body with new one
			$GLOBALS['TSFE']->content = preg_replace('/###HTML_BODY_SCRIPTMERGER###/is',
				$body, $GLOBALS['TSFE']->content);
		}

		// parse matches
		$length = count($matches[0]);
		$jsFiles = array();
		$dupes = array();
		$inDoc = '';
		for ($i = 0; $i < $length; ++$i)
		{
			// inDoc styles must be parsed to get only the js content
			if (empty($matches[2][$i])) {
				$pattern =
					'/\<script.*?\>' .									// begin of node
					'(?:.*?(?:\/\*|\/\/)?\<!\[CDATA\[(?:\*\/)?)?' .	// prefixed optional CDATA
					'(?:.*?\<!--)?' .										// senseless <!-- construct
					'\s*(.*?)' .												// js code (saved in \1)
					'(?:\s*\/\/\s*-->)?' .									// senseless <!-- construct
					'(?:\s*(?:\/\*|\/\/)?\]\]\>(?:\*\/)?)?' .			// postfixed end of optional CDATA
					'\s*\<\/script\>' .									// end of node
					'/is';														// no ungreedy modifier (causes problems)

				preg_match_all($pattern, $matches[0][$i], $content);
				//t3lib_div::debug($content);
				$inDoc .= $content[1][0] . "\n";
			}

			//save linked files into the js array
			if (!t3lib_div::isFirstPartOfStr($matches[2][$i], 'http://') &&
				!t3lib_div::isFirstPartOfStr($matches[4][$i], 'https://') &&
				!is_file(PATH_site . $matches[2][$i]))
				continue;

			// test matching with the ignore list
			$filename = basename($matches[2][$i]);
			$test = 0;
			foreach($this->extConfig['jsIgnoreList'] as $ignoreMatch)
				if ($test = preg_match('/' . $ignoreMatch . '/i', $filename))
					break;

			// only unique scripts; no match with the ignore list
			if ($test)
				$this->ignoredJsFiles[] = $matches[2][$i];
			elseif (!in_array($filename, $dupes))
				$jsFiles[] = $matches[2][$i];
			$dupes[] = $filename;
		}

		// write inDoc js into a file
		if (!empty($inDoc)) {
			$script = $this->extConfig['cachePath'] . 'javascript_inDoc_' . md5($inDoc) . '.js';
			if (!is_file(PATH_site . $script))
				t3lib_div::writeFile(PATH_site . $script, $inDoc);
			$jsFiles[] = $script;
		}

		return $jsFiles;
	}

	/**
	 * Replaces import rules
	 *
	 * @param $cssFile string css file which should be parsed for import rules
	 * @param $inDoc bool cssFile comes from generated inDoc styles
	 * @return array new files with import rule content if any
	 */
	function replaceImportRule($cssFile, $inDoc = false)
	{
		// get file content
		$prefix = !t3lib_div::isFirstPartOfStr($cssFile, 'http://') ? PATH_site : '';
		$fileContent = file_get_contents($prefix . $cssFile);

		// get import rules
		$excludeList = implode('|', $this->extConfig['cssIgnoreList']);
		$pattern =
			'/@import[\s]*' .		// must begin with @import
			'(?:url\()?[\'|"]?' .		// with or without url notation and/or quotes
			(empty($excludeList) ? '' :
				'(?!(' . $excludeList . '))') .	// exclude list for imports
			'([a-z0-9_\-\.\/\\\]+?)' .			// filename (\1)
			'[\'|"]?(?:\))?;' .			// with or without url notation and/or quotes
			'/is';
		$imports = array();
		preg_match_all($pattern, $fileContent, $imports);
		//t3lib_div::debug($imports);
		if (!count($imports[0]))
			return array($cssFile);

		$origFileContent = $this->replaceCSSurl(preg_replace($pattern, '', $fileContent,
			count($imports[0])), $cssFile);
		$origFileDir = !$inDoc ? dirname(str_replace(
			t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR'), '', $cssFile)) : '';

		// get file imports and write new file without imports
		$cssFiles = array();
		foreach ($imports[1] as $file) {
			if (!t3lib_div::isFirstPartOfStr($file, 'http://') &&
				!t3lib_div::isFirstPartOfStr($file, 'https://')) {
				$file = PATH_site . $origFileDir . '/' . $file;
				if (!is_file($file))
					continue;
			}

			$fileContent = $this->replaceCSSurl(file_get_contents($file), $file);
			$script = $this->extConfig['cachePath'] . 'stylesheet_import_' . md5($fileContent) . '.css';
			if (!is_file(PATH_site . $script))
				t3lib_div::writeFile(PATH_site . $script, $fileContent);
			$cssFiles[] = $script;
		}

		// original file
		$script = $this->extConfig['cachePath'] . 'stylesheet_importOrig_' .
			md5($origFileContent) . '.css';
		if (!is_file(PATH_site . $script))
			t3lib_div::writeFile(PATH_site . $script, $origFileContent);
		$cssFiles[] = $script;

		return $cssFiles;
	}

	/**
	 * Replaces all occurences of url in the css code by new paths
	 *
	 * @param string $content css code
	 * @param string $cssFile css file
	 * @return string fixed css code
	 */
	function replaceCSSurl($content, $cssFile)
	{
		$http = t3lib_div::isFirstPartOfStr($cssFile, 'http://') ||
			t3lib_div::isFirstPartOfStr($cssFile, 'https://') ? 1 : 0;
		$cssFile = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR'), '', $cssFile);
		$relative2CachePath = preg_replace('/.+\//U', '../', $this->extConfig['cachePath'], -1);

		$pattern = '/url\(' .				// any property wich contains a url reference
			'["\']?((?!\/)' .		// no absolute files (negative look-ahead)
			'[a-z0-9_\-\.\/\\\]+)' .			// file reference (\1)
			'["\']?\)' .					// ending of the url property
			'/ie';

		if ($this->extConfig['img2Base64']) {
			$replacement = '"url(data:image/" . substr("\1", strrpos("\1", ".")+1) . ";' . // mime type
				'base64," . base64_encode(file_get_contents("' .
					PATH_site . '" . (substr("\1", 0, strpos("\1", "/")) != "fileadmin" ? "' .
					dirname($cssFile) . '" : "") . "/\1")) . ") "'; // base64 encoded data
		} else {
			$dirname = ($this->extConfig['cssOutputType'] != 'inDoc' ?
				$relative2CachePath : '') . dirname($cssFile);
			$replacement = '"url(" . (substr("\1", 0, strpos("\1", "/")) == "fileadmin" ? "' .
				$relative2CachePath . '" : "' . $dirname . '") . "/\1) "';
		}

		//preg_match_all($pattern, $content, $matches); t3lib_div::debug($matches);
		return preg_replace($pattern, $replacement, $content, -1);
	}
}

?>
