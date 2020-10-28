<?php

namespace Proximify\PublicationImporter;

use Proximify\PublicationImporter\PublicationImporter;

/**
 * 
 * Extend the base class in order to add an additional 
 * location for the settings folder.
 */
class PublicationImporterCLI extends \Proximify\ForeignPackages
{
    const TEST_BIBTEX = __DIR__ . '/../dev/tests/assets/sample.bib';
    const TEST_GSCHOLAR = __DIR__ . '/../dev/tests/assets/sample.bib';
    const TEST_ENDNOTES = __DIR__ . '/../dev/tests/assets/endnote_library.xml';
    const TEST_PUBMED = 28375682;
    const TEST_DOI = 'DOI: 10.1177/0741713611402046 DOI:10.18653/v1/P17-1152';

    /**
     * @inheritDoc
     */
    static public function getSettingsFolder(): string
    {
        return  (__DIR__) . '/settings/cli';
    }

    function outputPublicationImport($params)
    {
        $type = $params['type'];

        if (empty($params['source']))
        {
             switch ($type)
            {
                case 'bibtex':
                    $source = self::TEST_BIBTEX;
                    break;
                case 'gscholar':
                    $source = self::TEST_GSCHOLAR;
                    break;
                case 'endnotes':
                    $source = self::TEST_ENDNOTES;
                    break;
                case 'pubmed':
                    $source = self::TEST_PUBMED;
                    break;
                case 'doi':
                    $source = self::TEST_DOI;
                    break;
            }
        }
        else {
            $source = $params['source'];
        }

        $importer = new PublicationImporter();
        print_r($importer->importPublications($type, $source));
    }

}