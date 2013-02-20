<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Xavier Perseguers <xavier@causal.ch>
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
 * Utility class.
 *
 * @category    Helpers
 * @package     TYPO3
 * @subpackage  tx_restdoc
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
final class tx_restdoc_utility {

	/**
	 * Returns a TYPO3-compatible list of menu entries.
	 *
	 * @param array $entries
	 * @return array
	 */
	public static function getMenuData(array $entries) {
		$menu = array();
		$entries = isset($entries['li'][0]) ? $entries['li'] : array($entries['li']);
		foreach ($entries as $entry) {
			$menuEntry = array(
				'title' => $entry['a']['@content'],
				'_OVERRIDE_HREF' => $entry['a']['@attributes']['href'],
			);
			if (isset($entry['ul'])) {
				$menuEntry['_SUB_MENU'] = self::getMenuData($entry['ul']);
			}

			$menu[] = $menuEntry;
		}

		return $menu;
	}

	/**
	 * Converts an XML string to a PHP array - useful to get a serializable value.
	 *
	 * @param string $xmlstr
	 * @return array
	 * @link https://github.com/gaarf/XML-string-to-PHP-array/blob/master/xmlstr_to_array.php
	 */
	public static function xmlstr_to_array($xmlstr) {
		$doc = new DOMDocument();
		$doc->loadXML($xmlstr);
		return self::domnode_to_array($doc->documentElement);
	}

	/**
	 * Converts a DOM node into an array.
	 *
	 * @param DOMNode $node
	 * @return array
	 */
	protected static function domnode_to_array(DOMNode $node) {
		$output = array();
		switch ($node->nodeType) {

			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;

			case XML_ELEMENT_NODE:
				for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
					$child = $node->childNodes->item($i);
					$v = self::domnode_to_array($child);
					if (isset($child->tagName)) {
						$t = $child->tagName;
						if (!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					}
					elseif ($v || $v === '0') {
						$output = (string) $v;
					}
				}
				if ($node->attributes->length && !is_array($output)) { // Has attributes but isn't an array
					$output = array('@content' => $output); // Change output into an array.
				}
				if (is_array($output)) {
					if ($node->attributes->length) {
						$a = array();
						foreach($node->attributes as $attrName => $attrNode) {
							$a[$attrName] = (string) $attrNode->value;
						}
						$output['@attributes'] = $a;
					}
					foreach ($output as $t => $v) {
						if (is_array($v) && count($v)==1 && $t !== '@attributes') {
							$output[$t] = $v[0];
						}
					}
				}
				break;
		}
		return $output;
	}

	/**
	 * Converts a relative path to an absolute one.
	 *
	 * @param string $fullPath
	 * @param string $relative
	 * @return string
	 */
	public static function relativeToAbsolute($fullPath, $relative) {
		$absolute = '';
		$fullPath = rtrim($fullPath, '/');
		$fullPathParts = explode('/', $fullPath);
		// We need an additional directory for parent paths to work (as we trimmed document name from $fullPath
		// in the caller method
		$fullPathParts[] = '';
		$relativeParts = explode('/', $relative);

		for ($i = 0; $i < count($relativeParts); $i++) {
			if ($relativeParts[$i] == '..' && count($fullPathParts) > 0) {
				array_pop($fullPathParts);
			} else {
				$absolute = implode('/', $fullPathParts) . '/';
				$absolute .= implode('/', array_slice($relativeParts, $i));
				break;
			}
		}

		return str_replace('//', '/', $absolute);
	}

}

?>