<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================

namespace Proximify\PublicationFetcher;

use \Exception;

abstract class PublicationSource
{
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

	protected $progress; // Used when parsing in extended classes
	protected $pubsInfo; // Both input and output argument

	protected $patentInProgressOption;
	protected $patentCompleteOption;

	function __construct($progress, $pubsInfo)
	{
		$this->progress = $progress;
		$this->pubsInfo = $pubsInfo;
		$this->sourceType = $this->pubsInfo['dataSource'];
	}

	abstract protected function parse($data, $encoding = '');

	protected function processData($data, $encoding, $checkAuthor)
	{
		if (!$data)
			return null;

		// Patents have some special options read from the CV
		// @todo Remove this and make it CV independent here.
		// $this->pubsInfo['patentStatusOptions'] = $this->gatherPatentStatusOptions();

		[$newPubs, $failed] = $this->parse($data, $encoding);

		foreach ($newPubs as $index => &$pub)
			$pub = $this->sanitizePub($pub);

		// To avoid mistakes
		unset($pub);

		if ($checkAuthor) {
			$newPubsSorted = $this->sortPubsByAuthoredByUser($newPubs);

			$newPubs = $newPubsSorted['authored'];
			$notTheAuthor = $newPubsSorted['notTheAuthor'];
			$unauthored = $newPubsSorted['unauthored'];
		} else {
			$notTheAuthor = [];
			$unauthored = [];
		}

		$this->pubsInfo['newPubs'] = $newPubs;
		$this->pubsInfo['notTheAuthor'] = $notTheAuthor;
		$this->pubsInfo['unauthoredPubs'] = $unauthored;
		$this->pubsInfo['failedImports'] = $failed;

		foreach ($this->pubsInfo['newPubs'] as $index => &$newPub) {
			if (isset($newPub['year']) && isset($newPub['date'])) {
				$date = $newPub['date'];
				$year = $newPub['year'];
				$dateArray = explode(' ', trim($date));

				if (count($dateArray) > 1)
					$date = implode('/', $dateArray);

				$date = $this->parseDate($year . '/' . $date);
				$dateArray = explode('/', $date);

				//if you get back more than just the year then save it.
				if (count($dateArray) > 1) {
					unset($this->pubsInfo['newPubs'][$index]['date']);
					unset($this->pubsInfo['newPubs'][$index]['year']);
					$newPub['year-date'] = $date;
				}
			}
		}

		// dbg_log($this->pubsInfo);

		return $this->pubsInfo;
	}

	protected function sanitizePub($pub)
	{
		$sectionName = $pub[self::BELONGS_TO] ?? '';
		$omit = [
			'ref-type',
			'refnum',
			'label',
			'notes',
			'author_address',
			// 'number',
		];

		$dateFields = [
			'date', 'year', 'publication_date', 'completion_date',
			'date_submitted', 'date_completed', 'date_posted_online',
			'date_first_released', 'date_issued'
		];

		foreach ($dateFields as $key) {
			if ($value = $pub[$key] ?? false)
				$pub[$key] = $this->parseDate($value);
		}

		// Normalize dates
		$pub = self::fixDateAmbiguity($pub);

		// Patents seem to have special requirements
		if ($sectionName == self::PATENT_SECTION)
			$pub = $this->setPatentInfo($pub);

		// Parse the DOI code
		if ($doi = $pub[self::DOI] ?? false)
			$pub[self::DOI] = $this->getDOI($doi);

		if ($url = $pub[self::URL] ?? false) {
			$url = urldecode($url);
			$pub[self::URL] = $url;
			// If the URL is a DOI, and there is no DOI set, use it
			if ($doi = $this->getDOI($url)) {
				if (empty($pub[self::DOI]))
					$pub[self::DOI] = $doi;
				// elseif ($url === $doi)
				// 	$pub['url'] = ''; // ignore URL value
			}
		}

		// Copy the sanitized values here
		$sanitizePub = [];

		foreach ($pub as $key => $value) {
			$key = strtolower($key);

			// Deal with array values
			if ($key == 'authors' && is_array($value))
				$value = self::implodeAuthorNames($value);
			elseif (is_array($value)) // eg, keywords
				$value = implode(', ', $value);

			// Maybe PubMed of gscholar used to have html entities
			// if ($type != 'endnotes' && $type != 'bibtex')
			// 	$value = html_entity_decode($value);

			$value = strip_tags($value);
			$value = trim($value);

			if ($key == self::TITLE)
				$value = trim($value, '.');

			if (in_array($key, $omit) || !$value || $value == 'null')
				continue;

			$sanitizePub[$key] = $value;
		}

		// if ($this->sourceType == 'bibtex')
		// 	$this->sanitizeBibText($pub, $sanitizePub);

		return $sanitizePub;
	}

	protected function setPatentInfo($pub)
	{
		//select patent status based on presence of patent/application number
		if (isset($pub['application number']) && empty($pub['patent number'])) {
			$pub['patent_status'] = $this->patentInProgressOption;

			if (isset($pub['publication date'])) {
				$pub['filing_date'] = $pub['publication date'];
				unset($pub['publication date']);
			}
		} elseif (isset($pub['patent number'])) {
			$pub['patent_status'] = $this->patentCompleteOption;

			if (isset($pub['publication date'])) {
				$pub['date_issued'] = $pub['publication date'];
				unset($pub['publication date']);
			}
		}

		//add country so that we can display the information when the user
		//goes to select country subdivision
		if (isset($pub['patent office'])) {
			$pub['patent_country'] = $pub['patent office'];
			unset($pub['patent office']);
		}

		// Check the application date
		$appDay = $pub['application_day'] ?? false;
		$appMonth = $pub['application_month'] ?? false;
		$appYear = $pub['application_year'] ?? false;

		if ($appDay && $appMonth && $appYear) {
			$pub['filing_date'] = $this->parseDate(
				$appYear . '/' . $appMonth . '/' . $appDay
			);
		} elseif ($appMonth && $appYear) {
			$pub['filing_date'] = $this->parseDate($appYear . '/' . $appMonth);
		} elseif ($appYear) {
			$pub['filing_date'] = $appYear;
		}

		unset($pub['application_year']);
		unset($pub['application_month']);
		unset($pub['application_day']);

		return $pub;
	}

	static function implodeAuthorNames($value)
	{
		// dbg_log($value);

		return implode(', ', array_filter($value));
	}

	// public function sanitizeBibText($pub)
	// {
	// 	if (isset($pub['date']) && isset($pub['year'])) {
	// 		$date = $this->parseDate($pub['date']);

	// 		$dateArray = explode('/', $date);

	// 		if (count($dateArray) > 1) {
	// 			$pub['date'] = $date;
	// 		} elseif (count($dateArray) == 1) {
	// 			$year = $this->parseDate($pub['year']);
	// 			$pub['date'] = (!$year) ? '' : $year . '/' . $date;
	// 		} else {
	// 			$pub['date'] = $this->parseDate($pub['year'] .
	// 				'/' . $pub['date']);
	// 		}

	// 		unset($pub['year']);
	// 	} elseif (isset($pub['date'])) {
	// 		$pub['date'] = $this->parseDate($pub['date']);
	// 	} elseif (isset($pub['year'])) {
	// 		$pub['date'] = $pub['year'];

	// 		unset($pub['year']);
	// 	}

	// 	return $pub;
	// }

	//fetch the lov id's for patent submission status
	//this is done in a couple of places depending on when we need them
	//used to avoid repeated code
	public function gatherPatentStatusOptions()
	{
		$memberProfile = new MembersProfile($this->modelParams(), Profile::CV_VIEW);
		$patentStatusOptions = $memberProfile->getSubtypeInfo(Profile::LOV_TYPE, 'Patent Status');

		foreach ($patentStatusOptions as $fieldID => $info) {
			if (strtolower($info[1]) == strtolower($this->dict->localStr(142))) {
				//completed
				$this->patentCompleteOption = $info;
			} else if (strtolower($info[1]) == strtolower($this->dict->localStr(143))) {
				//in progress
				$this->patentInProgressOption = $info;
			}
		}

		return $patentStatusOptions;
	}

	function parseDate($date)
	{
		$date = str_replace(['-', ',', ' ', "\n"], '/', trim($date));
		$parts = array_filter(explode('/', $date));

		if (!$parts)
			return '';

		$yearIdx = -1;
		$monthIdx = -1;

		// Search for YYYY and Month in words
		foreach ($parts as $idx => $part) {
			// Fist valid year
			if ($yearIdx < 0 && is_numeric($part) && strlen($part) == 4)
				$yearIdx = $idx;

			// First valid Month word
			if ($monthIdx < 0 && !is_numeric($part) && $this->getMonthInt($part))
				$monthIdx = $idx;
		}

		if ($yearIdx != 1 && $monthIdx < 0) // YYYY MM, or DD MM YYYY
			$monthIdx = 1;
		elseif ($yearIdx == 1 && $monthIdx < 0) // MM YYYY
			$monthIdx = 0;
		elseif ($yearIdx < 0 && $monthIdx == 1) // eg, DD MONTH YY, or YY MONTH
			$yearIdx = (count($parts) == 3) ? 2 : 0;

		$year = $parts[$yearIdx] ?? 0;

		if ($year && $monthIdx >= 0) {
			$month = $this->getMonthInt($parts[$monthIdx] ?? 0);
			// Get whatever is left
			$parts[$yearIdx] = $parts[$monthIdx] = 0;
			$day = $this->getDayInt(array_filter($parts)[0] ?? 0);
		} else {
			$month = 0;
			$day = 0;
		}

		$date = $year ?: '';

		if ($month) {
			$date .= '/' . $month;

			if ($day)
				$date .= '/' . $day;
		}

		return $date;

		// if (count($dateArray) > 1) {
		// 	if (strlen($date) == 7 && count($dateArray) == 2) {
		// 		// YYYY/MM
		// 		return $date;
		// 		// foreach ($this->abreviatedMonths as $k => $v) {
		// 		// 	if ($v == strtolower($dateArray[0]))
		// 		// 		return $k + 1;
		// 		// }
		// 		// return '';
		// 	} elseif (is_numeric($dateArray[1])) {
		// 		//check to see if month/date are in a messed order this might happen 
		// 		//when appending the date to year in endnote / bibtex pubs
		// 		if (count($dateArray) > 2 && !is_numeric($dateArray[2])) {
		// 			$monthVal = $this->getMonthInt($dateArray[2]);
		// 			if ($monthVal != 0) {
		// 				$newDate = $dateArray[0] . '/' . $monthVal . '/' . $dateArray[1];
		// 				$date = $newDate;
		// 			}
		// 		}
		// 		//otherwise do nothing $date is fine
		// 	} else {
		// 		$monthVal = $this->getMonthInt($dateArray[1]);

		// 		if ($monthVal != 0) {
		// 			$newDate = $dateArray[0] . '/' . $monthVal;
		// 			if (count($dateArray) > 2 && is_numeric($dateArray[2])) {
		// 				$newDate .= '/' . $dateArray[2];
		// 			}
		// 			$date = $newDate;
		// 		} else {
		// 			$date = $dateArray[0];
		// 		}
		// 	}
		// } else {
		// 	if (!is_numeric($date)) {
		// 		$date = '';
		// 	}
		// }
		// return $date;
	}

	static function getDayInt($day)
	{
		$day = intval($day);
		return ($day > 0 && $day <= 31) ? $day : 0;
	}

	// public static function parseDate($date)
	// {
	// 	if (!$date || !($date instanceof DOMElement))
	// 		return '';

	// 	$year = '';
	// 	$month = '';
	// 	$day = '';

	// 	foreach ($date->childNodes as $child) {
	// 		$key = $child->nodeName;
	// 		$value = $child->nodeValue;

	// 		switch ($key) {
	// 			case 'Year':
	// 				$year = $value;
	// 				break;
	// 			case 'Month':
	// 				$month = $value;
	// 				break;
	// 			case 'Day':
	// 				$day = $value;
	// 				break;
	// 		}
	// 	}

	// 	if ($year && $month) {
	// 		$year .= '/' . self::getMonthInt($month);

	// 		if ($day)
	// 			$year .= '/' . $day;
	// 	}

	// 	return $year;
	// }

	static function getMonthInt($val)
	{
		if (is_numeric($val)) {
			$val = intval($val);
			return ($val > 0 && $val <= 12) ? $val : 0;
		}

		$m1 = [
			'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4, 'may' => 5,
			'june' => 6, 'july' => 7, 'august' => 8, 'september' => 9, 'october' => 10,
			'november' => 11, 'december' => 12
		];

		$m2 = [
			'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5,
			'jun' => 6, 'jul' => 7, 'aug' => 8, 'sept' => 9, 'oct' => 10,
			'nov' => 12, 'dec' => 12
		];

		$m3 = [
			'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5,
			'juin' => 6, 'juillet' => 7, 'août' => 8, 'septembre' => 9, 'octobre' => 10,
			'novembre' => 11, 'décembre' => 12
		];

		$m4 = [
			'janv.' => 1, 'févr.' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5,
			'juin' => 6, 'juil.' => 7, 'août' => 8, 'sept.' => 9, 'oct.' => 10,
			'nov.' => 11, 'déc.' => 12
		];

		$val = strtolower($val);

		return $m1[$val] ?? $m2[$val] ?? $m3[$val] ?? $m4[$val] ?? 1;
	}

	static function getDOI($text)
	{
		// Checked with: https://www.phpliveregex.com/#tab-preg-match
		$pat = '/(http:\/\/|https:\/\/)?(dx\.doi\.org\/|doi\s*\:?\s*)?(10\..+)/i';

		return preg_match($pat, $text, $output) ? $output[3] : '';
	}

	/**
	 * There are situations, like 
	 * curl -LH "Accept: text/bibliography; style=bibtex" http://dx.doi.org/10.1080/08865655.2016.1188668
	 * in which we get a year and a date set to a month.
	 * {... year={2016}, month={Jul}, ...}
	 * In those cases, we should preserve the month. The CV "year" field accepts full dates.
	 * There might be other cases.
	 */
	static function fixDateAmbiguity($newPub)
	{
		if (isset($newPub['year']) && isset($newPub['date'])) {
			$date = $newPub['date'];
			$year = $newPub['year'];

			// The "date" might be okay if it's YEAR/MONTH/DAY so
			// we don't touch it. But if the date is a single word, 
			// it might be a month in words or number.
			if (is_numeric($year) && $date) {
				$parts = explode('/', str_replace(' ', '/', $date));

				if (count($parts) == 1 && (!is_numeric($date) || $date <= 12)) {
					$newPub['year'] = "$year/$date"; // This is one is what gets used
					$newPub['date'] = "$year/$date"; // just in case
					return true;
				}
			}
		}

		return $newPub;
	}

	// protected $abreviatedMonths = array(
	// 	'jan', 'feb', 'mar', 'apr', 'may',
	// 	'jun', 'jul', 'aug', 'sept', 'oct', 'nov', 'dec'
	// );
	// function getMonthInt($monthString)
	// {
	// 	$monthString = trim(strtolower($monthString));
	// 	$months = array(
	// 		'january', 'february', 'march', 'april', 'may',
	// 		'june', 'july', 'august', 'september', 'october', 'november', 'december'
	// 	);

	// 	$french = array(
	// 		'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
	// 		'septembre', 'octobre', 'novembre', 'décembre'
	// 	);
	// 	$frenchAbrev = array('janv.', 'févr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
	// 	$returnVal = 0;

	// 	$index = array_search($monthString, $months);
	// 	if ($index === 0) {
	// 		$returnVal = 1;
	// 	} elseif ($index != false) {
	// 		$returnVal = $index  + 1;
	// 	} else {
	// 		$index = array_search($monthString, $this->abreviatedMonths);
	// 		if ($index === 0) {
	// 			$returnVal = 1;
	// 		} elseif ($index != false) {
	// 			$returnVal = $index  + 1;
	// 		} elseif ($monthString == 'sep') {
	// 			//alternate form of sept that shows up.
	// 			$returnVal = 9;
	// 		} else {
	// 			$index = array_search($monthString, $french);
	// 			if ($index === 0) {
	// 				$returnVal = 1;
	// 			} elseif ($index != false) {
	// 				$returnVal = $index  + 1;
	// 			} else {
	// 				$index = array_search($monthString, $frenchAbrev);
	// 				if ($index === 0) {
	// 					$returnVal = 1;
	// 				} elseif ($index != false) {
	// 					$returnVal = $index  + 1;
	// 				}
	// 			}
	// 		}
	// 	}
	// 	return $returnVal;
	// }

	// static function checkIsset($array, $key, $default = null)
	// {
	// 	if ((!$key && $key !== 0) || !$array) {
	// 		return $default;
	// 	} elseif (is_array($key)) {
	// 		dbg_trace();
	// 		throw new Exception('checkisset: key cannot be ' . json_encode($key));
	// 	}

	// 	// Use default if $key does not exist in $array
	// 	if (!is_array($array))
	// 		return $default;
	// 	elseif (isset($array[$key]))
	// 		return $array[$key];
	// 	else
	// 		return $default;
	// }

	function sortPubsByAuthoredByUser($newpubs)
	{
		$userInf = $this->auth->getActiveUserInfo();
		$firstName = $this->removeAccents(strtolower($userInf['firstName']));
		$lastName = $this->removeAccents(strtolower($userInf['lastName']));
		$authored = array();
		$notTheAuthor = array();
		$unauthored = array();

		foreach ($newpubs as $endnotepub) {
			if (array_key_exists('authors', $endnotepub) || array_key_exists('editors', $endnotepub)) {
				$match = false;
				if (array_key_exists('authors', $endnotepub)) {
					$authors = $this->removeAccents(strtolower($endnotepub['authors']));
					$match = false;
					if (preg_match('/' . $firstName . '\\s+([a-z](\\.|\\s)\\s*)*' . $lastName . '/', $authors) == 1) {
						$match = true;
					} elseif (preg_match('/' . substr($firstName, 0, 1) . '(\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*' . $lastName . '/', $authors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s*\\,{1}\\s*' . $firstName . '(\\s+([a-z](\\.|\\s)\\s*)*)?' . '/', $authors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s*\\,{1}\\s*' . substr($firstName, 0, 1) . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $authors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s+' . $firstName . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $authors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s+' . substr($firstName, 0, 1) . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $authors) == 1) {
						$match = true;
					}

					if ($match) {
						$authored[] = $endnotepub;
					}
				}

				if (!$match && array_key_exists('editors', $endnotepub)) {
					$editors = $this->removeAccents(strtolower($endnotepub['editors']));

					if (preg_match('/' . $firstName . '\\s+([a-z](\\.|\\s)\\s*)*' . $lastName . '/', $editors) == 1) {
						$match = true;
					} elseif (preg_match('/' . substr($firstName, 0, 1) . '(\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*' . $lastName . '/', $editors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s*\\,{1}\\s*' . $firstName . '(\\s+([a-z](\\.|\\s)\\s*)*)?' . '/', $editors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s*\\,{1}\\s*' . substr($firstName, 0, 1) . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $editors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s+' . $firstName . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $editors) == 1) {
						$match = true;
					} elseif (preg_match('/' . $lastName  . '\\s+' . substr($firstName, 0, 1) . '((\\.|\\s)\\s*([a-z](\\.|\\s)\\s*)*)?' . '/', $editors) == 1) {
						$match = true;
					}

					if ($match) {
						$authored[] = $endnotepub;
					}
				}

				if (!$match) {
					$notTheAuthor[] = $endnotepub;
				}
			} else {
				$unauthored[] = $endnotepub;
			}
		}

		return array('authored' => $authored, 'notTheAuthor' => $notTheAuthor, 'unauthored' => $unauthored);
	}

	function removeAccents($string)
	{
		$unwanted_array = array(
			'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
			'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
		);

		return strtr($string, $unwanted_array);
	}
}
