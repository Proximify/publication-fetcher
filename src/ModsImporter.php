<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================
namespace Proximify\PublicationFetcher;

use Proximify\PublicationFetcher\DOMWalker;
use Proximify\PublicationFetcher\PublicationSource;
use \Exception;

/**
 * See http://www.loc.gov/standards/mods/mods-outline-3-7.htm
 * 
 * For BibTex formats, see
 * https://www.bibme.org/bibtex/chapter-citation/new
 * https://www.citethisforme.com/guides/bibtex/how-to-cite-a-chapter-of-an-edited-book
 * https://tex.stackexchange.com/questions/60843/how-to-cite-a-book-chapter-written-by-an-author-who-is-not-the-editor-or-author
 */
class ModsImporter extends PublicationSource
{
    /**
     * Imports files in Endnote or BibTex format. If $type is BibTex, the file is
     * converted to an Endnote file and then imported. Otherwise, it is assumed to be
     * an Endnote file in XML format.
     */
    function importFromFile($path, $type, $encoding, $checkAuthor)
    {
        $contents = ($type == 'mods') ? file_get_contents($path) :
            self::convertFileToMods($path, $type);

        return $this->processData($contents, $encoding, $checkAuthor);
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

        // $bibtexText = file_get_contents($source);
        // dbg_log($bibtexText);

        // // Not checking for ’ right now. It does not work.
        // // Not checking for \t{oo} either JS doesn't handle right now

        // $accentChar = '(\\\\[\'`"~=\.\^tudbHvVcCoOsS]{[aeiouncAEIOUNC]})';
        // $anyChar = '(.)';
        // $notLeftBracket = '([^\{])';
        // $notRightBracket = '([^\}])';
        // $rep = '$1{$2}$3';
        // $pattern = array(
        // 	'/' . $anyChar . $accentChar . $notRightBracket . '/',
        // 	'/' . $notLeftBracket . $accentChar . $anyChar . '/'
        // );

        // $bibtexText = preg_replace($pattern, [$rep, $rep], $bibtexText);

        // file_put_contents($source, $bibtexText);

        $outFileName = self::createTempFile('bibtex');

        $app = __DIR__ . '/c/bin/' . $binName;
        $cmd = "$app '{$source}' > '$outFileName'";

        $results = shell_exec($cmd);

        $contents = file_get_contents($outFileName);

        // The source file is a tmp file that should be deleted
        @unlink($outFileName);

        return $contents;
    }

    static function getAttribute($node, $name)
    {
        if (!$node->hasAttribute($name))
            return '';

        return $node->getAttribute($name);
    }

    static function getKeyValue($node, $key)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeName == $key)
                return $child->nodeValue;
        }

        return '';
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

    protected function parse($data, $encoding = '')
    {
        // dbg_log($data);

        $dom = new DOMWalker();

        // dbg_log($data);
        // if ($encoding != 'utf-8')
        // 	$data = utf8_encode($data);

        // $data = $this->normalizeWhites($data);
        // $data = $this->mb_convertSmartQuotes($data);

        if (!$dom->loadXMLFromString($data))
            throw new Exception($this->dict->localStr(348));

        return $this->parseModsCollection($dom);
    }

    protected function parseModsCollection($dom)
    {
        // dbg_log('START');
        // dbg_log($dom->saveXML());
        // dbg_log('END');
        $records = [];
        $failed = [];

        // $mods = $dom->xpathQueryElements('/modsCollection');

        // foreach ($mods as $item) {
        //     dbg_log($item);
        // }

        try {
            $root = $dom->childNodes->item(0);

            if ($root->nodeName != 'modsCollection')
                throw new Exception('Unexpected node type: ' . $root->nodeName);

            // } elseif (strtolower($domNode->childNodes->item(1)->nodeName) == 'records') {
            //     $recordsNode = $domNode->childNodes->item(1);
            // }
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

    /**
	 * Creates a file with a unique filename, with access permission set to 0600, 
	 * in the system's temporary directory. 
	 * @return Returns the new temporary filename, or FALSE on failure.
	 */
	static function createTempFile($prefix = 'xfy')
	{
		return tempnam(sys_get_temp_dir(), $prefix);
	}
}
