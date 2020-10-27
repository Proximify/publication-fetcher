<?php

require_once('../../vendor/autoload.php');

use Proximify\PublicationImporter\PublicationImporter;

$importer = new PublicationImporter();

print('Running tests...');

print('Importing from BibTex:');
$type = 'bibtex';
$source = 'assets/sample.bib';
$res = $importer->importFromFile($type, $source);
print_r($res);

print('Importing from Google Scholar:');
$type = 'bibtex';
$source = 'assets/sample.bib';
$res = $importer->importFromFile($type, $source);
print_r($res);

print('Importing from EndNotes:');
$type = 'endnotes';
$source = 'assets/endnote_library.xml';
$res = $importer->importFromFile($type, $source);
print_r($res);


print('Importing from Pubmed:');
$type = 'pubmed';
$source = 28375682;
$res = $importer->importFromFile($type, $source);
print_r($res);

print('Importing from DOI:');
$type = 'doi';
$source = 'DOI: 10.1177/0741713611402046 DOI:10.18653/v1/P17-1152';
$res = $importer->importFromFile($type, $source);
print_r($res);