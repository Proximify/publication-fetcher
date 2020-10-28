/*
 * xml2bib.c
 *
 * Copyright (c) Chris Putnam 2003-4
 *
 * Program and source code released under the GPL
 *
 */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include "newstr.h"
#include "lists.h"
#include "strsearch.h"
#include "fields.h"
#include "bibtexout.h"
#include "bibl.h"
#include "bibutils.h"
#include "bibprogs.h"

char  progname[] = "xml2bib";
lists asis = { 0, 0, NULL };
lists corps = { 0, 0, NULL };

void
tellversion( void )
{
	extern char bibutils_version[];
	extern char bibutils_date[];
	fprintf( stderr, "\n%s version, ", progname );
	fprintf( stderr, "bibutils suite version %s date %s\n",
		bibutils_version, bibutils_date );
}

void
help( void )
{
	tellversion();
	fprintf( stderr, "Converts the MODS XML intermediate reference file "
			"into Bibtex\n\n");

	fprintf(stderr,"usage: %s xml_file > bibtex_file\n\n",progname);
        fprintf(stderr,"  xml_file can be replaced with file list or omitted to use as a filter\n\n");

	fprintf(stderr,"  -h, --help         display this help\n");
	fprintf(stderr,"  -v, --version      display version\n");
	fprintf(stderr,"  -fc, --finalcomma  add final comman to bibtex output\n");
	fprintf(stderr,"  -sd, --singledash  use only one dash '-' instead of two '--' for page range\n" );
	fprintf(stderr,"  -b, -brackets      use brackets, not quotation marks surrounding data\n");
	fprintf(stderr,"  -w, --whitespace   use beautifying whitespace to output\n");
	fprintf(stderr,"  -sk, --strictkey   use only alphanumeric characters for bibtex key\n");
	fprintf(stderr,"                     (overly strict, but may be useful for other bibtex readers\n");
	fprintf(stderr,"  -s, --single-refperfile\n");
	fprintf(stderr,"                     one reference per output file\n");
	fprintf(stderr,"  --verbose          for verbose\n" );
	fprintf(stderr,"  --debug            for debug output\n" );
	fprintf(stderr,"\n");

	fprintf(stderr,"Citation codes generated from <REFNUM> tag.   See \n");
	fprintf(stderr,"http://www.scripps.edu/~cdputnam/software/bibutils for more details\n\n");
}

void
process_args( int *argc, char *argv[], param *p )
{
	int i, j, subtract;
	i = 1;
	while ( i<*argc ) {
		subtract = 0;
		if ( args_match( argv[i], "-h", "--help" ) ) {
			help();
			exit( EXIT_SUCCESS );
		} else if ( args_match( argv[i], "-v", "--version" ) ) {
			tellversion();
			exit( EXIT_SUCCESS );
		} else if ( args_match( argv[i], "-fc", "--finalcomma" ) ) {
			p->format_opts |= BIBOUT_FINALCOMMA;
			subtract = 1;
		} else if ( args_match( argv[i], "-s", "--single-refperfile" )){
			p->singlerefperfile = 1;
			subtract = 1;
		} else if ( args_match( argv[i], "-sd", "--singledash" ) ) {
			p->format_opts |= BIBOUT_SINGLEDASH;
			subtract = 1;
		} else if ( args_match( argv[i], "-b", "--brackets" ) ) {
			p->format_opts |= BIBOUT_BRACKETS;
			subtract = 1;
		} else if ( args_match( argv[i], "-w", "--whitespace" ) ) {
			p->format_opts |= BIBOUT_WHITESPACE;
			subtract = 1;
		} else if ( args_match( argv[i], "-sk", "--strictkey" ) ) {
			p->format_opts |= BIBOUT_STRICTKEY;
			subtract = 1;
		} else if ( args_match( argv[i], "-U", "--uppercase" ) ) {
			p->format_opts |= BIBOUT_UPPERCASE;
			subtract = 1;
		} else if ( args_match( argv[i], "-i", "--input-encoding" ) ) {
			args_encoding( *argc, argv, i, &(p->charsetin), 
					&(p->utf8in), progname );
			p->charsetin_src = BIBL_SRC_USER;
			subtract = 2;
		} else if ( args_match( argv[i], "-o", "--output-encoding" ) ) {
			args_encoding( *argc, argv, i, &(p->charsetout),
					&(p->utf8out), progname );
			p->charsetout_src = BIBL_SRC_USER;
			subtract = 2;
		} else if ( args_match( argv[i], "--verbose", "" ) ) {
			p->verbose = 1;
			subtract = 1;
		} else if ( args_match( argv[i], "--debug", "" ) ) {
			p->verbose = 3;
			subtract = 1;
		}
		if ( subtract ) {
			for ( j=i+subtract; j<*argc; ++j )
				argv[j-subtract] = argv[j];
			*argc -= subtract;
		} else i++;
	}
}

int 
main( int argc, char *argv[] )
{
	FILE *fp;
	param p;
	bibl b;
	int err, i;

	bibl_init( &b );
	bibl_initparams( &p, BIBL_MODSIN, BIBL_BIBTEXOUT );
	process_args( &argc, argv, &p );
	if ( argc == 1 ) {
		err = bibl_read( &b, stdin, "stdin", BIBL_MODSIN, &p ); 
		if ( err ) bibl_reporterr( err );
	} else {
		for ( i=1; i<argc; ++i ) {
			fp = fopen( argv[i], "r" );
			if ( fp ) {
				err = bibl_read( &b,fp,argv[i],BIBL_MODSIN,&p);
				if ( err ) bibl_reporterr( err );
				fclose( fp );
			} else {
				fprintf( stderr, "%s: cannot open %s\n",
					progname, argv[i]);
			}
		}
	}
	bibl_write( &b, stdout, BIBL_BIBTEXOUT, &p );
	fflush( stdout );

	fprintf( stderr, "%s: Processed %ld references.\n", progname, b.nrefs );
	fflush( stderr );

	bibl_free( &b );

	return EXIT_SUCCESS;
}


