<?php

require_once('../../vendor/autoload.php');

use Proximify\PublicationImporter\PublicationImporter;

$importer = new PublicationImporter();
$type = 'bibtex';
$source = 'assets/sample.bib';
$res = $importer->importFromFile($type, $source);

var_dump($res);
