<?php

require_once('../../vendor/autoload.php');

use Proximify\PublicationImporter\PublicationImporter;

$importer = new PublicationImporter();

print('Running tests...');

print('Importing from BibTex:');
$type = 'bibtex';
$source = getcwd() . '/assets/sample.bib';
$res = $importer->importPublications($type, $source);
print_r($res);

print('Importing from Google Scholar:');
$type = 'bibtex';
$source = getcwd() . '/assets/sample.bib';
$res = $importer->importPublications($type, $source);
print_r($res);

print('Importing from EndNotes:');
$type = 'endnotes';
$source = 'assets/endnote_library.xml';
$res = $importer->importPublications($type, $source);
print_r($res);


print('Importing from Pubmed:');
$type = 'pubmed';
$sources = [28375682, 20813019];

foreach ($sources as $source)
{   
    $res = $importer->importPublications($type, $source);
    print_r($res);
}

print('Importing from DOI:');
$type = 'doi';
$source = 'DOI: 10.1177/0741713611402046 DOI:10.18653/v1/P17-1152';
$res = $importer->importPublications($type, $source);
print_r($res);