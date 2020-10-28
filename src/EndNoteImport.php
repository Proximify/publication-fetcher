<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================

namespace Proximify\PublicationFetcher;

use Proximify\PublicationFetcher\PublicationSource;
use \Exception;

/**
 * See:
 * - http://www.ferroic.mat.ethz.ch/publications/xml_format
 */
class EndNoteImport extends PublicationSource
{
	function importFromFile($source, $type, $encoding, $checkAuthor)
	{
		if ($type == 'endnotes')
			$contents = file_get_contents($source);
		elseif ($type == 'bibtex')
			$contents = self::loadFromBibTex($source);
		else
			throw new Exception("Unknown EN reference file type '$type'");

		return $this->processData($contents, $encoding, $checkAuthor);
	}

	private static function loadFromBibTex($source)
	{
		$bibTexData = file_get_contents($source);

		// Not checking for ’ right now. It does not work.
		// Not checking for \t{oo} either JS doesn't handle right now

		$accentChar = '(\\\\[\'`"~=\.\^tudbHvVcCoOsS]{[aeiouncAEIOUNC]})';
		$anyChar = '(.)';
		$notLeftBracket = '([^\{])';
		$notRightBracket = '([^\}])';
		$rep = '$1{$2}$3';
		$pattern = array(
			'/' . $anyChar . $accentChar . $notRightBracket . '/',
			'/' . $notLeftBracket . $accentChar . $anyChar . '/'
		);

		$bibTexData = preg_replace($pattern, array($rep, $rep), $bibTexData);

		file_put_contents($source, $bibTexData);

		$tempFileName = self::createTempFile('bibtex');
		$outFileName = $tempFileName . '.xml';

		// Set the default output encoding to utf-8. Otherwise, the default in Linux is
		// ascii and in the Mac is utf-8
		$cmd = "cd ../libraries/bibtex-to-endnote-converter/bib2endnote-0.52 && " .
			"java -Dfile.encoding=UTF-8 BibEndnote '{$source}' '$outFileName'";

		$results = shell_exec($cmd);

		$contents = file_get_contents($outFileName);

		// The source file is a tmp file that should be deleted
		@unlink($outFileName);
		@unlink($tempFileName);

		return $contents;
	}

	protected function parse($data, $encoding = '')
	{
		$dom = new DOMWalker();

		if ($encoding != 'utf-8')
			$data = utf8_encode($data);

		$data = $this->normalizeWhites($data);
		$data = $this->mb_convertSmartQuotes($data);

		if (!$dom->loadXMLFromString($data))
			throw new Exception($this->dict->localStr(348));

		return $this->parseEndNoteDom($dom);
	}

	protected function parseEndNoteDom($dom)
	{
		// dbg_log('START');
		// dbg_log($dom->saveXML());
		// dbg_log('END');
		$records = [];
		$failed = [];

		try {
			$domNode = $dom->childNodes->item(0);
			if (strtolower($domNode->childNodes->item(0)->nodeName) == 'records') {
				$recordsNode = 	$domNode->childNodes->item(0);
			} elseif (strtolower($domNode->childNodes->item(1)->nodeName) == 'records') {
				$recordsNode = 	$domNode->childNodes->item(1);
			}
		} catch (Exception $e) {
			throw new Exception("Invalid file. Please upload an xml of your endnote library.");
		}

		if ($this->progress) {
			$progressDelta = 30 / count($recordsNode->childNodes);
			$currentProgress = 30;
		}

		foreach ($recordsNode->childNodes as $recordNode) {
			if ($this->progress) {
				$this->progress->update(
					'Converting data',
					$currentProgress,
					$currentProgress + $progressDelta
				);
			}

			if (strtolower($recordNode->nodeName) == 'record') {
				$records[] = $this->parseRecord($recordNode);
			}

			if ($this->progress) {
				$currentProgress += $progressDelta;
			}
		}

		return [$records, $failed];
	}

	protected function parseRecord($recordNode)
	{
		$record = [];
		$authors = [];

		foreach ($recordNode->childNodes as $itemNode) {
			//we need to replace _ with - because the bibtex translated files have
			//_ where the Endnote original files have -
			$nodeName = strtolower(str_replace("_", "-", $itemNode->nodeName));

			// dbg_log2($nodeName, $itemNode->nodeValue);

			switch ($nodeName) {
				case 'contributors':
				case 'titles':
				case 'urls':
				case 'related-urls':
				case 'pub-dates':
				case 'dates':
					$record = array_merge($record, $this->parseRecord($itemNode));
					break;
				case 'periodical':
					foreach ($itemNode->childNodes as $subItemNode) {
						if ($subItemNode->nodeName == 'full-title') {
							$record['periodical-title'] = strip_tags(trim($subItemNode->nodeValue));
						}
					}
					break;
				case 'year':
				case 'date':
				case 'organization':
				case 'institution':
				case 'title':
				case 'edition':
				case 'chapter':
				case 'secondary-title':
				case 'place-published':
				case 'publisher':
				case 'pub-location:':
				case 'volume':
				case 'number-of-volumes':
				case 'number':
				case 'pages':
				case 'section':
				case 'tertiary-title':
				case 'edition':
				case 'type-of-work':
				case 'short-title':
				case 'alternate-title':
				case 'isbn':
				case 'original-pub':
				case 'reprint-edition':
				case 'reviewed-item':
				case 'custom1':
				case 'custom2':
				case 'custom3':
				case 'custom4':
				case 'custom5':
				case 'custom6':
				case 'accession-number':
				case 'call-number':
				case 'label':
				case 'abstract':
				case 'notes':
				case 'url':
				case 'doi':
				case 'caption':
				case 'auth-address':
				case 'author-address':
					$recordKey = strtolower($itemNode->nodeName);
					$recordVal = strip_tags(trim($itemNode->nodeValue));

					// These two fields might contain URLs. Specially, a bibtex entry
					// with a url field is being mapped to author-address, but we
					// need it named url if it is a URL
					if ($nodeName == 'auth-address' || $nodeName == 'author-address') {
						if (empty($record['url']) || !self::isValidUrl($record['url'])) {
							if (self::isValidUrl($recordVal))
								$recordKey = 'url';
						}
					}

					$record[$recordKey] = $recordVal;
					break;
				case 'reference-type':
				case 'ref-type':
					if ($targetSection = self::getSectionFromRef($itemNode->nodeValue))
						$record['belongs-to'] = $targetSection;
					break;
				case 'keywords':
					$record['keywords'] = $this->getChildren($itemNode, ['keyword']);
					break;
				case 'authors':
				case 'secondary-authors':
				case 'tertiary-authors':
				case 'subsidiary-authors':
					if (!isset($record['authors']))
						$record['authors'] = [];

					$keys = ['author', 'secondary-author', 'tertiary-author', 'subsidiary-author'];

					$record['authors'] += $this->getChildren($itemNode, $keys);
					break;
			}
		}

		return $record;
	}

	static function getChildren($node, $keys)
	{
		$items = [];

		foreach ($node->childNodes as $child) {
			$name = strtolower(str_replace('_', '-', $child->nodeName));

			if (in_array($name, $keys))
				$items[] = trim($child->nodeValue);
		}

		return array_filter($items);
	}

	static function getSectionFromRef($refType)
	{
		$refType = intval(trim($refType));

		switch ($refType) {
			case 6:
				return 'books';
			case 28:
				return 'books';
			case 44:
				return 'books';
			case 5:
				return 'book_chapters';
			case 60:
				return 'book_chapters';
			case 10:
				return 'conference_publications';
			case 47:
				return 'conference_publications';
			case 17:
				return 'journal_articles';
			case 43:
				return 'journal_articles';
			case 52:
				return 'dictionary_entries';
			case 53:
				return 'encyclopedia_entries';
			case 19:
				return 'magazine_entries';
			case 23:
				return 'newspaper_articles';
			case 27:
				return 'reports';
			case 32:
				return 'dissertations';
			case 12:
				return 'online_resources';
			case 56:
				return 'online_resources';
			case 25:
				return 'patents';
			case 150:
				return 'manuals';
			case 200:
				return 'journal_issues';
			case 250:
				return 'online_resources';
		}

		return '';
	}
}
