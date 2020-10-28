<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================
// require_once('debug_utils.php');

namespace Proximify\PublicationFetcher;

use \DOMDocument;
use \DOMXpath;

class DOMWalker extends DOMDocument
{
	/** 
	 *	Raw data used to construct the DOM
	 */
	public $inputData = null;
	protected $xpath;

	function __construct()
	{
		parent::__construct();

		// $this->preserveWhiteSpace = false;
		// $this->formatOutput = false;
	}

	function init()
	{
		$this->xpath = null;
	}

	function getInputData()
	{
		return $this->inputData;
	}

	function loadHTML($source, $options = NULL)
	{
		$this->init();

		return parent::loadHTML($source, $options);
	}

	function loadXML($source, $options = NULL)
	{
		$this->init();

		return parent::loadXML($source, $options);
	}

	function loadHTMLFromURL($url)
	{
		$this->inputData = file_get_contents($url);

		return $this->loadHTML($this->inputData);
	}

	function loadXMLFromURL($url)
	{
		$this->inputData = file_get_contents($url);

		return $this->loadXML($this->inputData);
	}

	function loadXMLFromFile($filename)
	{
		$this->inputData = file_get_contents($filename);

		return $this->loadXML($this->inputData);
	}

	function loadXMLFromString($xmlString)
	{
		$this->inputData = $xmlString;

		return $this->loadXML($this->inputData);
	}

	/**
	 * Gets the first child that has the given tag name.
	 */
	function getFirstChildByTagName($node, $tag)
	{
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == $tag)
				return $child;
		}

		return null;
	}

	/**
	 * Gets all the child nodes that have the given tag name.
	 */
	function getChildNodesByTagName($node, $tag)
	{
		$nodes = array();

		foreach ($node->childNodes as $child) {
			if ($child->nodeName == $tag)
				$nodes[] = $child;
		}

		return $nodes;
	}

	function findAddNodesByTagName($node, $tag, &$nodes = array())
	{
		if ($node->nodeName == $tag)
			$nodes[] = $node;

		if ($node->childNodes) {
			foreach ($node->childNodes as $child)
				$this->findAddNodesByTagName($child, $tag, $nodes);
		}
	}

	/**
	 * Finds the first descendant element with the given Tag Name.
	 */
	function getFirstNodeByTagName($node, $name)
	{
		if ($node->nodeName == $name) {
			return $node;
		} else if ($node->hasChildNodes()) {
			foreach ($node->childNodes as $child) {
				$answer = $this->getFirstNodeByTagName($child, $name);

				if (is_object($answer))
					return $answer;
			}
		} else {
			return null;
		}
	}

	/**
	 * Finds the first descendant element with the given value.
	 */
	function getFirstNodeByValue($node, $value)
	{
		if ($node->nodeValue == $value) {
			return $node;
		} else if ($node->hasChildNodes()) {
			foreach ($node->childNodes as $child) {
				$answer = $this->getFirstNodeByValue($child, $value);

				if (is_object($answer))
					return $answer;
			}
		} else {
			return null;
		}
	}

	/**
	 * It creates an array with the value of all leaf nodes in the DOM subtree
	 * rooted at $root. BR tags are ignored.
	 */
	function explodeLeafNodes($root, &$values = array())
	{
		foreach ($root->childNodes as $child) {
			if ($child->hasChildNodes())
				$this->explodeLeafNodes($child, $values);
			else if ($child->nodeName != 'br')
				$values[] = $child->nodeValue;
		}

		return $values;
	}

	/**
	 * Gets the first table that has the given title as the vale of its 'thead' tag.
	 */
	function getTableByTitle($title)
	{
		$tables = $this->getElementsByTagName('table');
		$target = null;

		foreach ($tables as $table) {
			$head = $this->getFirstChildByTagName($table, 'thead');

			if (!is_null($head) && is_object($this->getFirstNodeByValue($head, $title)))
				return $table;
		}

		return null;
	}

	function parseTableRow($rowNode)
	{
		$cells = array();

		foreach ($rowNode->childNodes as $child) {
			if ($child->nodeName == 'td')
				$cells[] = $child;
		}

		return $cells;
	}

	function parseTable($tableNode)
	{
		$rows = array();

		if (is_object($tableNode)) {
			$body = $this->getFirstChildByTagName($tableNode, 'tbody');

			foreach ($body->childNodes as $child) {
				if ($child->nodeName == 'tr')
					$rows[] = $this->parseTableRow($child);
			}
		}

		return $rows;
	}

	function tableToValues(&$table)
	{
		foreach ($table as &$row)
			foreach ($row as &$cell)
				$cell = $cell->nodeValue;
	}

	function findPreviousSiblingByTagName($node, $siblingName)
	{
		for ($sib = $node->previousSibling; !is_null($sib); $sib = $sib->previousSibling)
			if ($sib->nodeName == $siblingName)
				return $sib;

		return null;
	}

	function getBodyNode()
	{
		$nodes = $this->getElementsByTagName('body');

		return $nodes->length ? $nodes[0] : false;
	}

	/**
	 * Converts all DIV and all text elements that are
	 * children of BODY into paragraph elements.
	 *
	 * @param bool $multiParOnly If true, paragraph nodes
	 * are created only if there is more than one paragraph. That 
	 * is, a single paragraph is never wrapped by <p>...</p>.
	 * @return DOMNode The body element, or false otherwise.
	 */
	function normalizeAsRichText($multiParOnly = true)
	{
		// Replace all DIVs with P elements
		$divs = $this->getElementsByTagName('div');

		foreach ($divs as $div)
			$this->replaceTag($div, 'p');


		$body = $this->getBodyNode();

		if (!$body)
			return false;

		// Replace all text elements that are children of BODY unless
		// there is a single text child and $multiParOnly is true
		if ($multiParOnly && $body->childNodes->length == 1) {
			$child = $body->childNodes[0];
			$isPlainText = ($child instanceof DOMText);

			// For consistency, unwrap a text node that is wrapped by
			// a node with no attributes (eg, a <p>...</p> wrapper).
			if (
				!$isPlainText && !$child->hasAttributes() &&
				$child->childNodes->length == 1 &&
				$child->childNodes[0] instanceof DOMText
			) {
				// Unwrap the grand child text node
				$body->replaceChild($child->childNodes[0], $child);
				$isPlainText = true;
			}
		} else {
			$isPlainText = false;
		}

		if (!$isPlainText)
			$this->wrapTextNodes($body, 'p');

		return $body;
	}

	/**
	 * Wrap each child text nodes with a $wrapperTag element.
	 */
	function wrapTextNodes($root, $wrapperTag)
	{
		$leaves = [];

		$root->normalize();

		foreach ($root->childNodes as $child) {
			if ($child instanceof DOMText && !$child->isWhitespaceInElementContent())
				$leaves[] = $child;
		}

		foreach ($leaves as $child) {
			$wrapper = $wrapperTag;

			if ($child->nodeValue == "\xc2\xa0")
				$wrapper = 'span';

			$newNode = $this->createElement($wrapper);
			$root->replaceChild($newNode, $child);
			$newNode->appendChild($child);
		}
	}

	function getInnerHTML($node)
	{
		$html = '';

		foreach ($node->childNodes as $child)
			$html .= $this->saveHTML($child);

		return $html;
	}

	/**
	 * Normalizes the HTML as a sequence of paragraphs, and returns
	 * the inner HTML of the body element.
	 *
	 * @return string
	 */
	// function getRichText()
	// {
	// 	$body = $this->normalizeAsRichText();

	// 	return $body ? $this->getInnerHTML($body) : '';
	// }

	function replaceTag($oldNode, $newTag)
	{
		//echo "Replacing: " . $this->saveHTML($oldNode);

		// Create a new DOM element in $newItem with new tag...
		$newNode = $this->createElement($newTag);

		// ... and set updated attributes on this new DOM element
		if ($oldNode->hasAttributes()) {
			foreach ($oldNode->attributes as $attr)
				$newNode->setAttribute($attr->nodeName, $attr->nodeValue);
		}

		// Inject all child nodes from $searchedElement into the new DOM element
		// Create a DOM element in $newNode that is a clone of $child and with all $child's nodes,
		// and append this DOM element to the new DOM element

		foreach ($oldNode->childNodes as $child)
			$newNode->appendChild($child->cloneNode(true));

		// Once the new DOM element is ready, append it to the $newItem
		$oldNode->parentNode->replaceChild($newNode, $oldNode);
	}

	function getElementsByClass($class)
	{
		$expression = './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';

		return $this->xpathQueryElements($expression);
	}

	/**
	 * Queries elements from the dom using a jQuery like selector.
	 * @todo 
	 * XPath uses / as a parent/child delimiter, while CSS/jQuery selectors use >.
	 * XPath uses one-indexed square brackets to denote index, whereas jQuery uses the :nth-child() pseudo-selector
	 * So:
	 * 
	 * var
	 * xpath = '/html/body/div[4]/div[2]/div/div/div/ul/li[4]',
	 * jq_sel = xpath
	 * .substr(1) //discard first slash
	 * .replace(/\//g, ' > ')
	 * .replace(/\[(\d+)\]/g, function($0, i) { return ':nth-child('+i+')'; });
	 *
	 * @param string $expression See https://devhints.io/xpath
	 * @return DOMNodeList
	 */
	function selectElements($query)
	{
		$expression = $query; // @todo conversion needed here

		return $this->xpath->xpathQueryElements($expression);
	}

	/**
	 * Queries elements from the dom using an xpath expression.
	 *
	 * @param string $expression See https://devhints.io/xpath
	 * @return DOMNodeList
	 */
	function xpathQueryElements($expression)
	{
		if (!$this->xpath)
			$this->xpath = new DOMXpath($this);

		return $this->xpath->query($expression);
	}
}
