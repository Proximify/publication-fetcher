<?php

namespace Proximify\PublicationFetcher;

/**
 * File for class DependencyInstaller.
 *
 * @author    Proximify Inc <support@proximify.com>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   https://opensource.org/licenses/GPL-3.0 GNU GPL 3.0
 * @version   1.0.0 GLOT Run Time Library
 */

class DependencyInstaller extends \Proximify\ForeignPackages
{
    function checkDependencies() {
        printf('Checking dependencies...');
        $output = $this->findApp('make');

        if(empty($output))
            print('You need "make" in your system to run this package.');

        $workingDir = __DIR__ . '/c';
        printf('Creating binaries...');
        $output = $this->execute('make', $workingDir);
 
        if (!empty($output['error']))
            print('An error occured:' . $output['error']);
    }
}
