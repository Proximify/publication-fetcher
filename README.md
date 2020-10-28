# publication-fetcher

publication-fetcher is a composer package that imports publication lists from multiple sources such as BibTex file, Google Scholar, EndNote file, PubMed identifers and DOIs.

- [Table of contents](docs/toc.md)

## Getting Started
publication-fetcher both offers an API and a CLI to import publications.

## Installation

<pre>
    composer require proximify/publication-fetcher
</pre>

or create a project:

<pre>
    composer create-project proximify/publication-fetcher
</pre>

**Note:** If you install publication-fetcher as dependency (i.e. installing with the require option), you have to create the binary files manually. To do that, go to C folder under src and run "make" command.

## API

The publication-fetcher imports from bibtex, Google Scholar (bibtex) and endnode files. Sample bibtex and endnodes files are available docs folder.

<pre>
    use Proximify\PublicationImporter\PublicationImporter;
    
    $importer = new PublicationImporter();
    $type = 'bibtex';
    $source = 'PATH_TO_FILE;
    $res = $importer->importPublications($type, $source);
    print_r($res);
</pre>

It also fetches publications from PubMed references (i.e. PubMed ids)

<pre>
    print('Importing from Pubmed:');
    $type = 'pubmed';
    $sources = [28375682, 20813019];

    foreach ($sources as $source)
    {   
        $res = $importer->importPublications($type, $source);
        print_r($res);
    }
</pre>

and DOIs:

<pre>
    print('Importing from DOI:');
    $type = 'doi';
    $source = 'DOI: 10.1177/0741713611402046 DOI:10.18653/v1/P17-1152';
    $res = $importer->importPublications($type, $source);
    print_r($res);
</pre>


## Testing
publication-fetcher offers a CLI, a web interface and a test suite for testing purposes.

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
    php -S localhost:8000 -t dev/www
</pre>

**Note:** Please set the correct path for the autoloader.php under the dev/www/api/index.php and dev/tests/Test.php. Otherwise, the test script will fail.


## Contributing
This project welcomes contributions and suggestions. Most contributions require you to agree to a Contributor License Agreement (CLA) declaring that you have the right to and actually do, grant us the rights to use your contribution. For details, visit our Contributor License Agreement.

When you submit a pull request, we will determine whether you need to provide a CLA and decorate the PR appropriately (e.g., label, comment). Simply follow the instructions provided. You will only need to do this once across all repos using our CLA.

This project has adopted the Proximify Open Source Code of Conduct. For more information see the Code of Conduct FAQ or contact support@proximify.com with any additional questions or comments.

## License
Copyright (c) Proximify Inc. All rights reserved.

Licensed under the MIT license.

publication-fetcher is made by Proximify. We invite the community to participate.