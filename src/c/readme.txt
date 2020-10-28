
                              COMPILING BIBUTILS.

Step 1.  Configure the makefile by running the configure script.

% configure

or alternatively

% csh -f configure


Bibutils Configuration
----------------------

Configured Makefile to operating system Linux_x86.
    If auto-identification of operating system failed, please
    e-mail cdputnam@ucsd.edu with the system type and output of
    the command: uname -a

Set installation directory to /usr/local/bin.
    To modify install directory type: configure --install-dir DIR
    where DIR is the desired directory.

To compile,          type: make
To install,          type: make install
To make tgz package, type: make package
To make deb package, type: make deb


Step 2.  Make the package with make

% make

Step 3.  Install the package

% make install


