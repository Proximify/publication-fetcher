<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================
namespace Proximify\PublicationImporter;

use Proximify\PublicationImporter\PublicationSource;
use Proximify\PublicationImporter\RemoteConnection;
use Proximify\PublicationImporter\DomWalker;
use \Exception;
use \DOMElement;

class PubMedImport extends PublicationSource
{
	/**
	 * The domain is listed in root/core/known_hosts.ini as requiring
	 * a VPN connection. This is to avoid the limit on number of
	 * request per second (10 with API key, and 3 without).
	 */
	const PUBMED_ULR = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi';
	const INVALID_ID = 'invalid_id';

	function importFromPubmed($ids)
	{
		// $newPubs = $this->parse($ids);

		return $this->processData($ids, '', false);
	}

	protected function parse($idStr, $encoding = '')
	{
		$separators = [';', "\n", '-', "'", '"'];


		$idStr = str_replace($separators, ',', $idStr);

		$pmIds = explode(',', $idStr);

		$newPubs = [];
		$failed = [
			'invalid_urls' => [],
			'failed_imports' => []
		];

		// if ($this->progress) {
		// 	$progressDelta = 60 / count($pmIds);
		// 	$currentProgress = 0;
		// }

		foreach ($pmIds as $index => $pmId) {

			// if ($this->progress) {
			// 	$this->progress->update(
			// 		$this->dict->localStr(150, $pmId),
			// 		$currentProgress,
			// 		$currentProgress + $progressDelta
			// 	);
			// }

			// There are some restrictions to the number of requests per second
			// (3 with no API token, and 10 with one). But this applies to all
			// concurrent users. To be on the safe side, we can just do 1/second
			if ($index)
				sleep(1);

            $addPub = $this->fetchFromId(trim($pmId));            

			if ($addPub == self::INVALID_ID) {
				$failed['invalid_urls'][] = $pmId;
			} elseif (!$addPub) {
				$failed['failed_imports'][] = $pmId;
			} else {
                $pub = $this->sanitizePubMed($addPub);
                
				$newPubs[] = $pub;
			}

			// if ($this->progress)
			// 	$currentProgress += $progressDelta;
		}

		return [$newPubs, $failed];
	}

	/**
	 * Read: 
	 * - https://www.ncbi.nlm.nih.gov/books/NBK25497/
	 * - http://www.ferroic.mat.ethz.ch/publications/xml_format
	 */
	function fetchFromId($pubMedArticleID)
	{
		if (!is_numeric($pubMedArticleID))
            return self::INVALID_ID;
        

		$params = [
			'db' => 'pubmed',
			'id' => $pubMedArticleID,
			'report' => 'xml',
			'tool' => 'uniweb',
			'email' => 'support@proximify.ca',
			'api_key' => 'eeb6b49f90f11e8fdfa42093a71cf2522c08'
		];

		$conn = new RemoteConnection();
        $dom = new DOMWalker();


        
		try {
            $pubMedXML = $conn->get(self::PUBMED_ULR, $params);
            

			if (!$dom->loadXMLFromString($pubMedXML))
                throw new Exception("Cannot parse PubMed data");



                
			$articles = $dom->xpathQueryElements('/PubmedArticleSet/PubmedArticle/MedlineCitation/Article');


			if (!$articles->length)
				throw new Exception("Cannot find PM article");

            $article = $articles->item(0);


            $article = $this->parseArticleTag($article);            

			$article['pmid'] = $pubMedArticleID;
		} catch (Exception $e) {
			dbg_log($e->getMessage());
			return self::INVALID_ID;
		}

		return $article;
	}

	private function sanitizePubMed($pub)
	{
		$sanitized = [];

		foreach ($pub as $key => $value)
			$sanitized[strtolower($key)] = trim(strip_tags($value));

		return $sanitized;
	}

	function parseArticleTag($article)
	{
        $record = [];

		if (!$article instanceof DOMElement)
            return $record;            

		$keys = [
			'ArticleTitle' => 'title', 'ArticleDate' => 'publication_date',
			'AuthorList' => 'authors', 'AbstractText' => 'description',
			'MedlinePgn' => 'pages'
		];

		foreach ($article->childNodes as $child) {
			$key = $child->nodeName;
			$value = $child->nodeValue;

			switch ($key) {
				case 'Pagination':
				case 'Abstract':
					$record = array_merge($record, $this->parseArticleTag($child));
					break;
				case 'Journal':
					$record = array_merge($record, $this->parseJournalTag($child));
					break;
				case 'ArticleTitle':
				case 'MedlinePgn':
				case 'AbstractText':
					$record[$keys[$key]] = $value;
					break;
				case 'ArticleDate':
					if ($value)
						$record[$keys[$key]] = $this->parseDate($child);
					break;
				case 'AuthorList':
					$authors = [];

					foreach ($child->childNodes as $author) {
						if ($name = $this->parseAuthorName($author))
							$authors[] = $name;
					}

					if ($authors)
						$record[$keys[$key]] = implode(', ', $authors);

					break;
			}
		}

		// PubMed seems to only have journal articles
		$record['belongs-to'] = 'journal_articles';

		return $record;
	}

	function parseAuthorName($author)
	{
		if (!$author || !($author instanceof DOMElement))
			return '';

		$names = [];

		foreach ($author->childNodes as $child) {
			$key = $child->nodeName;
			$value = $child->nodeValue;

			switch ($key) {
				case 'LastName':
					array_unshift($names, $value);
					break;
				case 'ForeName':
					$names[] = $value;
					break;
			}
		}

		return implode(' ', $names);
	}

	function parseJournalTag($journal)
	{
		$record = [];

		if (!$journal instanceof DOMElement)
			return $record;

		foreach ($journal->childNodes as $child) {
			$key = $child->nodeName;
			$value = $child->nodeValue;

			switch ($key) {
				case 'JournalIssue':
					$record = array_merge($record, $this->parseJournalTag($child));
					break;
				case 'Volume':
				case 'Issue':
				case 'Title':
					$record['journal_' . $key] = $value;
					break;
				case 'PubDate':
					$date = $this->parseDate($child);
					$record['journal_' . $key] = $date;
					$record['publication_date'] = $date;
					break;
			}
		}

		return $record;
	}

	function parseDate($date)
	{
		if (!$date || !($date instanceof DOMElement))
			return '';

		$year = '';
		$month = '';
		$day = '';

		foreach ($date->childNodes as $child) {
			$key = $child->nodeName;
			$value = $child->nodeValue;

			switch ($key) {
				case 'Year':
					$year = $value;
					break;
				case 'Month':
					$month = $value;
					break;
				case 'Day':
					$day = $value;
					break;
			}
		}

		if ($year && $month) {
			$year .= '/' . $this->getMonthInt($month);

			if ($day)
				$year .= '/' . $day;
		}

		return $year;
	}
}
