<?php
/**
 * Class Minify_ImportProcessor
 * @package Minify
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Linearize a CSS/JS file by including content specified by CSS import
 * declarations. In CSS files, relative URIs are fixed.
 *
 * @imports will be processed regardless of where they appear in the source
 * files; i.e. @imports commented out or in string content will still be
 * processed!
 *
 * This has a unit test but should be considered "experimental".
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_ImportProcessor {

    public static $filesIncluded = array();

    public static function process($file)
    {
        self::$filesIncluded = array();
        self::$_isCss = (strtolower(substr($file, -4)) === '.css');
        $obj = new Minify_ImportProcessor(dirname($file));
        return $obj->_getContent($file);
    }

    // allows callback funcs to know the current directory
    private $_currentDir = NULL;

    // allows _importCB to write the fetched content back to the obj
    private $_importedContent = '';

    private static $_isCss = NULL;

    private function __construct($currentDir)
    {
        $this->_currentDir = $currentDir;
    }

// ##################### BEGIN TYPO3 modification

	/**
	 * @var array
	 */
	public static $extensionConfiguration = array();

// ##################### END TYPO3 modification

    private function _getContent($file)
    {
    	$originalPath = $file;
		$file = realpath($file);
        if (! $file
            || in_array($file, self::$filesIncluded)
            || FALSE === ($content = @file_get_contents($file))
        ) {
            // file missing, already included, or failed read
            return '';
        }
        self::$filesIncluded[] = $file;
        $this->_currentDir = dirname($file);

// ##################### BEGIN TYPO3 modification
		if (strpos($this->_currentDir, realpath(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT'))) === FALSE) {
			if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0.0', '<')) {
				$pathSite = PATH_site;
				$pathTypo3 = PATH_typo3;
			} else {
				$pathSite = Environment::getPublicPath() . '/';
				$pathTypo3 = Environment::getPublicPath() . '/typo3/';
			}

			if (!strpos($file, $pathSite) === FALSE) {
				// fix path to the typo3 core
				$realPathToTYPO3 = str_replace('typo3/', '', realpath($pathTypo3));
				$this->_currentDir = str_replace($realPathToTYPO3, '', $this->_currentDir);
				$this->_currentDir = realpath($pathSite) . '/typo3' . $this->_currentDir;
			} else {
				// a symlinked extension can cause realpath to make a mess, so we fetch the dirname without realpath
				$this->_currentDir = dirname($originalPath);
			}
		}
// ##################### END TYPO3 modification

        // remove UTF-8 BOM if present
        if (pack("CCC",0xef,0xbb,0xbf) === substr($content, 0, 3)) {
            $content = substr($content, 3);
        }
        // ensure uniform EOLs
        $content = str_replace("\r\n", "\n", $content);

        // process @imports
        $content = preg_replace_callback(
            '/
                @import\\s+
                (?:url\\(\\s*)?      # maybe url(
                [\'"]?               # maybe quote
                (.*?)                # 1 = URI
                [\'"]?               # maybe end quote
                (?:\\s*\\))?         # maybe )
                ([a-zA-Z,\\s]*)?     # 2 = media list
                ;                    # end token
            /x'
            ,array($this, '_importCB')
            ,$content
        );

        if (self::$_isCss) {
            // rewrite remaining relative URIs
            $content = preg_replace_callback(
                '/(?<!fill=\')url\\(\\s*([^\\)\\s]+)\\s*\\)/'
                ,array($this, '_urlCB')
                ,$content
            );
        }

        return $this->_importedContent . $content;
    }

    private function _importCB($m)
    {
        $url = $m[1];
        $mediaList = preg_replace('/\\s+/', '', $m[2]);

        if (strpos($url, '://') > 0) {
            // protocol, leave in place for CSS, comment for JS
            return self::$_isCss
                ? $m[0]
                : "/* Minify_ImportProcessor will not include remote content */";
        }
        if ('/' === $url[0]) {
            // protocol-relative or root path
            $url = ltrim($url, '/');
            $file = realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        } else {
            // relative to current path
            $file = $this->_currentDir . DIRECTORY_SEPARATOR
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        }
        $obj = new Minify_ImportProcessor(dirname($file));
        $content = $obj->_getContent($file);
        if ('' === $content) {
            // failed. leave in place for CSS, comment for JS
            return self::$_isCss
                ? $m[0]
                : "/* Minify_ImportProcessor could not fetch '{$file}' */";
        }
        return (!self::$_isCss || preg_match('@(?:^$|\\ball\\b)@', $mediaList))
            ? $content
            : "@media {$mediaList} {\n{$content}\n}\n";
    }

    private function _urlCB($m)
    {
        // $m[1] is either quoted or not
        $quote = ($m[1][0] === "'" || $m[1][0] === '"')
            ? $m[1][0]
            : '';
        $url = ($quote === '')
            ? $m[1]
            : substr($m[1], 1, strlen($m[1]) - 2);
        if ('/' !== $url[0]) {
            if (strpos($url, '//') > 0) {
                // probably starts with protocol, do not alter
// ##################### BEGIN TYPO3 modification
			} elseif (strpos($url, 'data:image') !== FALSE) {
				// probably starts with an inline image, do not alter
// ##################### END TYPO3 modification
            } else {
                // prepend path with current dir separator (OS-independent)
                $path = $this->_currentDir
                    . DIRECTORY_SEPARATOR . strtr($url, '/', DIRECTORY_SEPARATOR);
                // strip doc root
                $path = substr($path, strlen(realpath($_SERVER['DOCUMENT_ROOT'])));
                // fix to absolute URL
                $url = strtr($path, '/\\', '//');
                // remove /./ and /../ where possible
                $url = str_replace('/./', '/', $url);
                // inspired by patch from Oleg Cherniy
                do {
                    $url = preg_replace('@/[^/]+/\\.\\./@', '/', $url, 1, $changed);
                } while ($changed);
            }
        }

// ##################### BEGIN TYPO3 modification

		if (trim(self::$extensionConfiguration['css.']['postUrlProcessing.']['pattern']) !== '') {
			$pattern = self::$extensionConfiguration['css.']['postUrlProcessing.']['pattern'];
			$replacement = self::$extensionConfiguration['css.']['postUrlProcessing.']['replacement'];
			$url = preg_replace($pattern, $replacement, $url);
		} elseif (is_array(self::$extensionConfiguration['css.']['postUrlProcessing.'])
			&& !empty(self::$extensionConfiguration['css.']['postUrlProcessing.'])
		) {
			foreach (self::$extensionConfiguration['css.']['postUrlProcessing.'] as $configuration) {
				if (!isset($configuration['pattern']) || !isset($configuration['replacement'])) {
					continue;
				}

				$pattern = $configuration['pattern'];
				$replacement = $configuration['replacement'];
				$url = preg_replace($pattern, $replacement, $url);
			}
		}

// ##################### END TYPO3 modification

        return "url({$quote}{$url}{$quote})";
    }
}
