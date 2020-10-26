<?php

/**
 * Test file for the h-index.
 *
 * @author    Proximify Inc <support@proximify.com>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   https://opensource.org/licenses/GPL-3.0 GNU GPL 3.0
 * @version   1.0
 */
require_once __DIR__ . '/../../../vendor/autoload.php';

use Proximify\PublicationImporter;

$action = $_POST['action'];

switch ($action) {
    case 'importFromURL':
        $output = importFromURL();
        break;
    case 'importFromFile':
        $output = importFromFile();
        break;
    default:
        throw new Exception('Invalid action: ' . $action);
}

echo json_encode($output);

function importFromURL() {
    $type = $_POST['type'];
    $source = $_POST['source'];
    $importer = new PublicationImporter\PublicationImporter();
    $res =  $importer->importFromFile($type, $source);

    return $res;
}

function importFromFile() {
    $type = $_POST['type'];
    $source = $_FILES['source']['tmp_name'];
    $importer = new PublicationImporter\PublicationImporter();
    $res =  $importer->importFromFile($type, $source);

    return $res;
}