# publication-importer

publication-importer is a composer package that imports publication lists from multiple sources such as BibTex file, Google Scholar, EndNote file, PubMed identifers and DOIs.

- [Table of contents](docs/toc.md)

## Getting Started
publications-importer both offers an API and a CLI to import publications.

## Installation

<pre>
    composer require proximify/publication-importer
</pre>

or create a project:

<pre>
    composer create-project proximify/publication-importer
</pre>

## API

<pre>
    use Proximify\PublicationImporter\PublicationImporter;
    
    $importer = new PublicationImporter();
    $type = 'bibtex';
    $source = 'PATH_TO_FILE;
    $res = $importer->importFromFile($type, $source);
    print_r($res);
</pre>


## Testing
publication-importer offers a CLI, a web interface and a test suite for testing purposes.

CLI:
<pre>
    composer import-pubs
</pre>

Test suite

<pre>
    php dev/tests/Test.php
</pre>

You can also use the web interface for testing under the dev folder:

<pre>
    cd publication-importer/dev/www && php -S localhost:8000
</pre>

**Note:** Please set the correct path for the autoloader.php under the dev/www/api/index.php. Otherwise, the test script will fail.


## Contributing
This project welcomes contributions and suggestions. Most contributions require you to agree to a Contributor License Agreement (CLA) declaring that you have the right to and actually do, grant us the rights to use your contribution. For details, visit our Contributor License Agreement.

When you submit a pull request, we will determine whether you need to provide a CLA and decorate the PR appropriately (e.g., label, comment). Simply follow the instructions provided. You will only need to do this once across all repos using our CLA.

This project has adopted the Proximify Open Source Code of Conduct. For more information see the Code of Conduct FAQ or contact support@proximify.com with any additional questions or comments.

## License
Copyright (c) Proximify Inc. All rights reserved.

Licensed under the MIT license.

publication-importer is made by Proximify. We invite the community to participate.