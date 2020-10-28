<?php

namespace Proximify\PublicationFetcher;

use Proximify\PublicationFetcher\EndNoteImport;
use Proximify\PublicationFetcher\PubMedImport;
use Proximify\PublicationFetcher\ModsImporter;
use \Exception;

/** 
 *
 * @author Mert Metin <mert@proximify.ca>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   MIT
 * @version   1.0 UNIWeb Module
 */

class PublicationFetcher { 
    const DEFAULT_LIBRARY = 'bibutils';
    const AVAILABLE_LIBRARIES = ['BibUtils'];
    const HASH_KEYS_PATH = __DIR__ . '/../settings/publicationHashKeys.yaml'; // was .ini before
	const FIELD_MAP_PATH = __DIR__ . '/../settings/publicationFieldMap.yaml'; // was .ini before
    const SETTINGS_FILE = '../settings/PublicationFetcher.json';
    const TITLE_KEY = 'TITLE_KEY';
	const PUB_DATE_KEY = 'PUBDATE_KEY';
	const UNDEFINED_SECTION = 'undefined';

    public $progress;

    const BELONGS_TO = 'belongs-to';
	const PAGES = 'pages';
	const URL = 'url';
	const DOI = 'doi';
	const TITLE = 'title';

    const BOOK_SECTION = 'books';
	const BOOK_CHAPTER_SECTION = 'book_chapters';
	const DISSERTATION_SECTION = 'dissertations';
	const PATENT_SECTION = 'patents';
	const JOURNAL_SECTION = 'journal_articles';
	const CONFERENCE_SECTION = 'conference_publications';
    const SECONDARY_AUTHORS = 'secondary_authors';  

    /**
     * @var @options Component options.
     */
    public $options;

   /**
    * C'tor
    * @param $options Component options.
    */
    function __construct($options = []) 
    {
        $this->options['lang'] = empty($this->options['lang']) ? 'en' : $options['lang'];
    }

    /**
     * Returns the list of settings under the settings/HIndexReader.json.
     * 
     * @return string 
     */
    function getSettings()
    {
        return json_decode(file_get_contents(__DIR__ . '../' . self::SETTINGS_FILE), true);
    }

    function getTranslationDict()
    {
        if ($this->options['lang'] == 'en')
        {
            $dict =  [
                'date_issued' => 'Date issued',
                'filing_date' => 'Filing date',
                'date_of_end_term' => 'Date of end of term',
            ];
        }
        else {
            $dict =  [
                'journal name' => 'Nom de la revue',
                'volume' => 'Volume',
                'issue' => 'Numéro',
                'pages' => 'Pages',
                'description' => 'Description',
                'book title' => 'Titre de l\'ouvrage',
                'abstract' => 'Résumé',
                'conference name' => 'Nom de la conférence',
                'publication date' => 'Date de publication',
                'date_issued' => 'Date de délivrance',
                'filing_date' => 'Date de dépôt',
                'date_of_end_term' => 'Fin de la période de validité',
                'patent office' => 'Office des brevets',
                'patent number' => 'Numéro du brevet',
                'publisher' => 'Éditeur',
                'institution' => 'Établissement'
            ];
        }

        return $dict;
    }

    public function sanitizeStringForComparison($string)
	{
		// Second arg adds a period to the default chars to trim
		return strtolower(trim($string, ". \t\n\r\0\x0B"));
	}

    function fetchDOI($doiList)
	{
		foreach ($doiList as &$doi) {
			$url = 'https://dx.doi.org/' . trim($doi);
			$doi = '"' . $url . '"';
        }

        $argList = implode(' ', $doiList);
        
        $cmd = 'curl -w "\n" -LH "Accept: application/x-bibtex" ' . $argList;

		// dbg_log($cmd);
		@exec($cmd, $rawInput, $retVar);
		// dbg_log($rawInput);

		if (!$rawInput)
			throw new Exception('Invalid DOI response');

		// It seems that the x-bibtex header doesn't separate each reference
		// with a new line, which is required my the MODS converter.
		// see https://citation.crosscite.org/docs.html
		// SOLVED WITH -w "\n"
		// if (count($doiList) > 1)
		// 	$rawInput = str_replace('}@', "}\n@", $rawInput);

		if (is_array($rawInput))
			$rawInput = implode("\n", $rawInput);

		return $this->createSourceFile($rawInput);
    }

    function createSourceFile($data)
	{
		$source = $this->createTempFile();

        file_put_contents($source, $data);
        
		return $source;
    }

    protected function duplicatePubs($indexKey)
	{
		// The pub data is hashed first by the length of the key, which is in turn
		// made out of title, date and other parameters.
		$len = strlen($indexKey);

		// dbg_log($len);
		// dbg_log($this->publicationsData);

		if (isset($this->publicationsData[$len]) && isset($this->publicationsData[$len][$indexKey])) {
			//'id' => $repeated,
			$repeatedItem = $this->publicationsData[$len][$indexKey][0];

			return [
				'section' => $repeatedItem['section'],
				'pubTitle' => ucfirst($repeatedItem['title'])
			];
		}
	}
    
	function importPublications($type, $source, $checkAuthor = false, $deleteAfterImport = false)
	{
        if ($type == 'doi') {
			// Change the type
			$type = 'bibtex';

			// Normalize separators to spaces
			$source = str_replace([',', ';', "\n"], ' ', $source);

			$doiList = array_filter(explode(' ', $source));
		} else {
			$doiList = [];
        }

        if ($type == 'gscholar')
            $type = 'bibtex';

        // $this->loadPublicationAndIntelectualPropertyProfileData();


        $duplicateCriteria = $this->getDuplicateCriteria();

        
        $pubsInfo = [
			'dataSource' => $type,
			'failedImports' => [],
			'notTheAuthor' => [],
			'unauthoredPubs' => [],
			'newPubs' => [],
			'translationDictionary' => $this->getTranslationDict(),
			'oldPubs' => []
        ];

        // $this->sectionFieldMap = $this->loadSectionFieldMap($type);
        
		switch ($type) {
			case 'pubmed':
				$imp = new PubMedImport($this->progress, $pubsInfo);
				$pubsInfo = $imp->importFromPubmed($source);
				$currentProgress = 80;
				break;

			case 'endnotes':
				// $source = $this->getUploadedFilePath();
				$pubsInfo = $this->importFromFile($source, $type, $pubsInfo, $checkAuthor);
				$currentProgress = 30;
				break;
			case 'bibtex':
				if ($doiList) 
					$source = $this->fetchDOI($doiList);
                
				$pubsInfo = $this->importFromFile($source, $type, $pubsInfo, $checkAuthor);
				$currentProgress = 30;

                if ($deleteAfterImport)
				    @unlink($source);
				break;
        }
        

        // $this->getOtherPubInfo($pubsInfo, $currentProgress, $duplicateCriteria);

        return $pubsInfo;

    }

    function importFromFile($path, $type, $pubsInfo, $checkAuthor)
    {
        $imp = new ModsImporter($this->progress, $pubsInfo);

		$this->validateTextFile($path);
		$encoding = self::getFileCharset($path);

        $imported = $imp->importFromFile($path, $type, $encoding, $checkAuthor);
        
		// Try with the old method if we run into a problem
		if (is_null($imported)) {
			$imp = new EndNoteImport($this->progress, $pubsInfo);
			$imported = $imp->importFromFile($path, $type, $encoding, $checkAuthor);
		}

		return $imported ?? $pubsInfo;
    }



    private function getDuplicateCriteria()
	{
        $duplicateCriteria = empty($this->options['duplicateCriteria']) ? null
             : $this->options['duplicateCriteria'];

		if ($duplicateCriteria) {
			if (is_string($duplicateCriteria))
				$duplicateCriteria = explode(',', $duplicateCriteria);

			if (is_array($duplicateCriteria)) {
				//make sure no invalid keys are in the criteria array to prevent errors
				$exceptions = array_diff($duplicateCriteria, array('year', 'month'));

				if ($exceptions)
					$duplicateCriteria = array_diff($duplicateCriteria, $exceptions);
			}
		}

		return $duplicateCriteria;
	}


    public function getOtherPubInfo(&$pubsInfo, $currentProgress, $duplicateCriteria)
	{
		$progressDelta = (95 - $currentProgress) / max(count($pubsInfo['newPubs']), 1);

		// Remove repeated pubs from new pubs
		$repeatedPubs = $this->findRepeatedPublications(
			$pubsInfo['newPubs'],
			$duplicateCriteria,
			$currentProgress,
			$progressDelta
        );   
    }

    public function getPubIndexKeyForSearch($publication, $duplicateCriteria)
	{
		$pubTitle = $this->findKeyValue($publication, self::TITLE_KEY);

		if (!$pubTitle) {
			// dbg_log("Cannot find title in pub data");
			// dbg_log($publication);
			return null;
		}

		$indexKey = $this->sanitizeStringForComparison($pubTitle);

		if ($duplicateCriteria) {
			$pubDate = $this->findKeyValue($publication, self::PUB_DATE_KEY);
			$pubDate = $this->parseDateStringIntoDateHash($pubDate);

			if ($pubDate) {
				foreach ($duplicateCriteria as $criteria) {
					if ($criteria == 'year' || $criteria == 'month' || $criteria == 'day')
						$indexKey .= $pubDate[$criteria];
				}
			}
		}
		// dbg_log($indexKey);
		return $indexKey;
	}



    function findRepeatedPublications(&$newpubs, $duplicateCriteria, $currentProgress, $progressDelta)
	{
		$repeatedPubs = [];
		$pubs = [];

		foreach ($newpubs as $publication) {
			// $this->progress->update(
			// 	$this->dict->localStr(149),
			// 	$currentProgress,
			// 	$currentProgress + $progressDelta
			// );

			$currentProgress += $progressDelta;

			$repeatedItem = false;
			$repeatedBy = '';

			$indexKey = $this->getPubIndexKeyForSearch($publication, $duplicateCriteria);

			$repeatedBy = $this->duplicatePubs($indexKey);

			$belongsTo = $publication['belongs-to'] ?? self::UNDEFINED_SECTION;
			$publication['belongs-to'] = $this->section_names_to_ids[$belongsTo] ?? 0;

			if ($repeatedBy) {
				// Add the publication to the array that is $repeatedBy from the 
				// duplicatePubs function so that we will have all the publication data 
				// to print later others:('section' => $belongsTo, 'pubTitle' => $publicationTitle)
				$repeatedPubs[] = array_merge($repeatedBy, ['publication' => $publication]);
			} else {
				$pubs[] = $publication;
			}
		}

		$newpubs = $pubs;

		return $repeatedPubs;
	}

    private function findKeyValue($publication, $keyType)
	{
		$sectionName = $publication['belongs-to'] ?? self::UNDEFINED_SECTION;

		$fieldMap = $this->sectionFieldMap[$sectionName] ?? [];

		$srcKeys = $fieldMap[$keyType] ?? []; // either self::TITLE_KEY or self::PUB_DATE_KEY
		$value = '';

		if (!$srcKeys)
			print("WARNING: There are no keys for section '$sectionName'!!!!");

		// Find the first known title key with a value
		foreach ($srcKeys as $srcKey) {
			if ($value = $publication[$srcKey] ?? '')
				break;
		}

		return $value;
	}

    function processData($data, $encoding)
    {
        if (!$data)
            return null;
            
        $dom = new DOMWalker();

        if (!$dom->loadXMLFromString($data))
            throw new Exception('Unable to load the XML.');

        return $this->parseModsCollection($dom);
    }

    protected function parseModsCollection($dom)
    {
        $records = [];
        $failed = [];

        try {
            $root = $dom->childNodes->item(0);

            if ($root->nodeName != 'modsCollection')
                throw new Exception('Unexpected node type: ' . $root->nodeName);

        } catch (Exception $e) {
            throw new Exception('Invalid MODS XML DOM. ' . $e->getMessage());
        }

        if ($this->progress) {
            $progressDelta = 30 / count($root->childNodes);
            $currentProgress = 30;
        }

        foreach ($root->childNodes as $recordNode) {
            if ($this->progress) {
                $this->progress->update(
                    'Converting data',
                    $currentProgress,
                    $currentProgress + $progressDelta
                );
            }

            if ($recordNode->nodeName == 'mods')
                $records[] = $this->parseRecord($recordNode);

            if ($this->progress)
                $currentProgress += $progressDelta;
        }

        return [$records, $failed];
    }

    protected function parseRecord($recordNode)
    {
        $record = [];

        // dbg_log($recordNode);

        foreach ($recordNode->childNodes as $itemNode) {
            $key = $itemNode->nodeName;
            // $value = $itemNode->nodeValue;

            switch ($key) {
                case 'titleInfo':
                    $record['title'] = $this->getKeyValue($itemNode, 'title');

                    // Subtitle handling is discussed here: 
                    // https://asklibrary.com.edu/faq/57125
                    $subtitle = $this->getKeyValue($itemNode, 'subTitle');

                    if ($subtitle)
                        $record['title'] .= ': ' . $subtitle;
                    break;
                case 'name':
                    $type = self::getAttribute($itemNode, 'type');

                    if (!isset($record['authors']))
                        $record['authors'] = [];

                    // Should we check for 'personal' or not?
                    // if ($type == 'personal')
                    $record['authors'][] = self::parseAuthorName($itemNode);
                    break;
                case 'genre':
                    // The genre can be at the root level or within a <relatedItem type="host">
                    self::setTargetSection($itemNode, $record);
                case 'originInfo':
                    // There originInfo can be at the root level or within a <relatedItem type="host">
                    // We give priority to <relatedItem type="host"> items!
                    if (empty($record['date']))
                        $record['date'] = $this->getKeyValue($itemNode, 'dateIssued');

                    if (empty($record['publisher']))
                        $record['publisher'] = $this->getKeyValue($itemNode, 'publisher');

                    break;
                case 'relatedItem':
                    self::parseHost($itemNode, $record);
                    break;
                case 'identifier':
                    $type = self::getAttribute($itemNode, 'type');

                    if ($type == 'doi')
                        $record[self::DOI] = $itemNode->nodeValue;
                    break;
                case 'location':
                    if ($url = $this->getKeyValue($itemNode, 'url'))
                        $record[self::URL] = $url;
                    break;
                case 'part':
                    // Only if we have a valid date
                    if ($date = $this->getKeyValue($itemNode, 'date'))
                        $record['date'] = $date;

                    $record += self::parseDetails($itemNode);
                    break;
            }
        }

        // Books with page numbers are actually book chapters
        $pages = $record[self::PAGES] ?? false;
        $section = $record[self::BELONGS_TO] ?? '';

        // If there are pages and is marked as a book, then it's likely a book chapter
        if ($pages && $section == self::BOOK_SECTION)
            $record[self::BELONGS_TO] = self::BOOK_CHAPTER_SECTION;

        // dbg_log($record);
        return $record;
    }

    private static function parseAuthorName($name)
    {
        $given = [];
        $family = [];

        foreach ($name->childNodes as $child) {
            if ($child->nodeName == 'namePart') {
                $type = $child->getAttribute('type');

                if ($type == 'given')
                    $given[] = $child->nodeValue;
                elseif ($type == 'family')
                    $family[] = $child->nodeValue;
            }
        };

        if (!$family && count($given) == 2 && $given[0] == 'et' && $given[1] == 'al.')
            return 'et al.';

        $family = implode(' ', $family);

        if ($init = $given[0][0] ?? false)
            $family .= " $init.";

        return $family;
    }

    static function parseHost($node, &$record)
    {
        $type = self::getAttribute($node, 'type');

        if ($type != 'host')
            return [];

        foreach ($node->childNodes as $child) {

            switch ($child->nodeName) {
                case 'genre':
                    self::setTargetSection($child, $record);
                    break;
                case 'originInfo':
                    if ($pub = self::getKeyValue($child, 'publisher'))
                        $record['publisher'] = $pub;

                    if ($place = self::getKeyValue($child, 'place'))
                        $record['publication_location'] = $place; // it has a child 'placeTerm'

                    if (empty($record['date']))
                        $record['date'] = self::getKeyValue($child, 'dateIssued');
                    break;
                case 'titleInfo':
                    if ($title = self::getKeyValue($child, 'title'))
                        $record['periodical-title'] = $title;
                    break;
                case 'part':
                    $record += self::parseDetails($child);
                    break;
                case 'name':
                    if (!isset($record['authors']))
                        $record[self::SECONDARY_AUTHORS] = [];

                    // Should we check for 'personal' or not?
                    if (self::getAttribute($child, 'type') == 'personal')
                        $record[self::SECONDARY_AUTHORS][] = self::parseAuthorName($child);
            }
        }
    }

    static function parseDetails($node)
    {
        $r = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeName == 'detail') {
                $type = self::getAttribute($child, 'type');
                switch ($type) {
                    case 'volume':
                    case 'number':
                        $r[$type] = $child->nodeValue;
                        break;
                    case 'page':
                        // Special single page case
                        // @see docs/Development/bibutils.md
                        $start = self::getKeyValue($child, 'number');
                        $r[self::PAGES] = "$start - $start";
                        break;
                }
            } elseif ($child->nodeName == 'extent') {
                $unit = self::getAttribute($child, 'unit');

                if ($unit == 'page') {
                    $start = self::getKeyValue($child, 'start');
                    $end = self::getKeyValue($child, 'end');
                    $r[self::PAGES] = ($start && $end) ? "$start - $end" : $start;
                }
            }
        }

        return $r;
    }

    static function setTargetSection($node, &$record)
    {
        // !$child->attributes->length
        $authority = self::getAttribute($node, 'authority');
        $type = $node->nodeValue;

        // If authority is "marcgt", see the list of options 
        // MARC Genre Term List
        // - https://www.loc.gov/standards/valuelist/marcgt.html
        // - saved at tools/bibutils

        switch ($type) {
            case 'conference':
            case 'conference publication';
                $section = self::CONFERENCE_SECTION;
                break;
            case 'academic journal':
            case 'journal':
            case 'periodical':
            case 'issue':
            case 'article':
                $section = self::JOURNAL_SECTION;
                break;
            case 'patent':
            case 'patent number':
            case 'patent office':
                $section = self::PATENT_SECTION;
                break;
            case 'institution':
            case 'dissertation':
            case 'thesis':
            case 'theses':
                $section = self::DISSERTATION_SECTION;
                break;
            case 'Ph.D. thesis':
                // @todo This is probably not working
                $record['degree_type'] = 'Doctorate';
                $section = self::DISSERTATION_SECTION;
                break;
            case 'book_chapter':
            case 'collection':
                $section = self::BOOK_CHAPTER_SECTION;
                break;
            case 'book':
            case 'handbook':
                $section = self::BOOK_SECTION;
                break;
            default:
                $section = false;
                // Generic formula to create standard names
                // $section = empty($record[self::BELONGS_TO]) ?
                //     str_replace(' ', '_', strtolower($type)) : '';
        }

        // Valid Uniweb sections are:
        // [undefined] => 0
        // [journal_articles] => 124
        // [journal_issues] => 126
        // [books] => 128
        // [book_chapters] => 130
        // [dissertations] => 136
        // [encyclopedia_entries] => 146
        // [dictionary_entries] => 150
        // [reports] => 152
        // [working_papers] => 154
        // [manuals] => 156
        // [online_resources] => 158
        // [clinical_care_guidelines] => 162
        // [conference_publications] => 164
        // [book_reviews] => 132
        // [newspaper_articles] => 142
        // [magazine_entries] => 148
        // [patents] => 205

        if ($section)
            $record[self::BELONGS_TO] = $section;
    }

    static function getAttribute($node, $name)
    {
        if (!$node->hasAttribute($name))
            return '';

        return $node->getAttribute($name);
    }

    private static function convertFileToMods($source, $type)
    {
        if ($type == 'bibtex') {
            $binName = 'bib2xml';
        } elseif ($type == 'endnotes') {
            $ext = pathinfo($source, PATHINFO_EXTENSION);
            $binName = (strtolower($ext) == 'enl') ? 'end2xml' : 'endx2xml';
        } else {
            throw new Exception("No bin to convert file type '$type'");
        }

        $outFileName = self::createTempFile('bibtex');
        $app = __DIR__ . '/c/bin/' . $binName;
        $cmd = "$app '{$source}' > '$outFileName'";



        $results = shell_exec($cmd);
        $contents = file_get_contents($outFileName);

        // The source file is a tmp file that should be deleted
        @unlink($outFileName);

        return $contents;
    }

	/**
	 * Creates a file with a unique filename, with access permission set to 0600, 
	 * in the system's temporary directory. 
	 * @return Returns the new temporary filename, or FALSE on failure.
	 */
	static function createTempFile($prefix = 'xfy')
	{
		return tempnam(sys_get_temp_dir(), $prefix);
    }
    
    static function getFileCharset($filename)
	{
		$mime = self::getFileMIMEType($filename);

		$prefix = 'charset=';
		$len = strlen($prefix);

		foreach ($mime as $value) {
			if (substr($value, 0, $len) == $prefix)
				return substr($value, $len);
		}

		return false;
    }
    
    /**
	 * Gets the mime type of a file. 
	 *
	 * RFC 2045 syntax (http://lists.w3.org/Archives/Public/xml-dist-app/2003Jul/0064.html)
	 * For example,
	 *
	 * zip file: application/zip; charset=binary
	 * tiff image: image/tiff; charset=binary
	 * xml ascii: application/xml; charset=us-ascii
	 * xml utf-8: application/xml; charset=utf-8
	 * bib tex: text/x-tex; charset=us-ascii
	 *
	 * @return array An array with [type, subtype, parameter, parameter, ...]. 
	 * For example, ['text', 'x-tex', 'charset=us-ascii']
	 */
	static function getFileMIMEType($filename)
	{
		$results = array();

		$args = (PHP_OS == 'Darwin') ? '-bI' : '-bi';

		exec("file $args $filename", $results);

		if (!$results)
			throw new Exception("Cannot determine file type");

		$mime = $results[0];

		return preg_split("/[\s;\/]+/", $mime);
    }
    
    static function getKeyValue($node, $key)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeName == $key)
                return $child->nodeValue;
        }

        return '';
    }


    function getHashKeys ()
    {
        // return Yaml::parseFile(self::HASH_KEYS_PATH);
    }


     function loadSectionFieldMap($type) 
     {
        // $fieldMap = Yaml::parseFile(self::FIELD_MAP_PATH);
        $keys = $this->getHashKeys();

        	// Get the UNIWeb field names
		foreach ($this->getHashKeys() as $parentSection => $sections) {
			foreach ($sections as $sectionName => $keys) {
				$titleField = $keys[self::TITLE_KEY] ?? '';
				$dateField = $keys[self::PUB_DATE_KEY] ?? '';

				$fields = $fieldMap[$sectionName]['fields'] ?? [];
				$titleKeys = [];
				$dateKeys = [];

				foreach ($fields as $refField => $uniField) {
					if ($uniField == $titleField)
						$titleKeys[] = $refField;

					if ($uniField == $dateField)
						$dateKeys[] = $refField;
				}

				if (!in_array('title', $titleKeys))
					$titleKeys[] = 'title';

				if (!in_array('date', $dateKeys))
					$dateKeys[] = 'date';

				if (!in_array('year-date', $dateKeys))
					$dateKeys[] = 'year-date';

				$fieldMap[$sectionName][self::TITLE_KEY] = $titleKeys;
				$fieldMap[$sectionName][self::PUB_DATE_KEY] = $dateKeys;
			}
		}

		// Add the "default" case so it's populated too
		$fieldMap[self::UNDEFINED_SECTION][self::TITLE_KEY] = ['title'];
		$fieldMap[self::UNDEFINED_SECTION][self::PUB_DATE_KEY] = ['date'];

		return $fieldMap;
     }


    /**
	 * Gets the temp path to the uploaded file. 
	 */
	function getUploadedFilePath($propertyName = null)
	{
		$fileInfo = $this->getUploadedFile($propertyName);

		return $fileInfo['tmp_name'];
	}

    function getUploadedFile($propertyName = null)
	{
		if (!$propertyName)
			$propertyName = 'user_file';

		$fileInfo = array();

		if (!isset($_FILES[$propertyName]))
			throw new Exception('File upload error: No file is selected');

		// Sometimes, a property of the file description might be an array, but
		// it shouldn't, so we make it a regular value
		foreach ($_FILES[$propertyName] as $key => $value)
			$fileInfo[$key] = is_array($value) ? $value[0] : $value;

		if ($fileInfo['error'] != UPLOAD_ERR_OK) {
			switch ($fileInfo['error']) {
				case c:
				case UPLOAD_ERR_FORM_SIZE:
					$msg = $this->dict->localStr(302);
					break;
				case UPLOAD_ERR_EXTENSION:
					$msg = $this->dict->localStr(303);
					break;
				default:
					$msg = $this->dict->localStr(304);
					break;
			}

			throw new Exception($msg);
		}

		return $fileInfo;
    }
    
	function validateTextFile($filename)
	{
		if (self::getFileCharset($filename) == 'binary')
			throw new Exception($this->dict->localStr(348));
	}
}