/*
 * xml2isi.c
 *
 * Copyright (c) Chris Putnam 2007
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
#include "bibl.h"
#include "bibutils.h"
#include "bibprogs.h"

char  progname[] = "xml2isi";
lists asis = { 0, 0, NULL };
lists corps = { 0, 0, NULL };

void
tellversion( void )
{
	extern char bibutils_version[], bibutils_date[];
	fprintf( stderr, "%s, ", progname );
	fprintf( stderr, "bibutils suite version %s date %s\n",
		bibutils_version, bibutils_date );
}

void
help( void )
{
	tellversion();
	fprintf(stderr,"Converts an XML intermediate reference file into ISI format\n\n");

	fprintf(stderr,"usage: %s xml_file > isi_file\n\n",progname);
        fprintf(stderr,"  xml_file can be replaced with file list or omitted to use as a filter\n\n");

	fprintf(stderr,"  -s, --single-refperfile one reference per output file\n");
	fprintf(stderr,"  -h, --help     display this help\n");
	fprintf(stderr,"  --verbose      for verbose output\n");
	fprintf(stderr,"  --debug        for debug output\n");
	fprintf(stderr,"  -v, --version  display version\n\n");

	fprintf(stderr,"http://www.scripps.edu/~cdputnam/software/bibutils for more details\n\n");
}

void
process_args( int *argc, char *argv[], param *p )
{
	int i, j, subtract;
	i = 1;
	while ( i < *argc ) {
		subtract = 0;
		if ( args_match( argv[i], "-h", "--help" ) ) {
			help();
			exit( EXIT_SUCCESS );
		} else if ( args_match( argv[i], "-v", "--version" ) ) {
			tellversion();
			exit( EXIT_SUCCESS );
		} else if ( args_match( argv[i], "-s", "--single-refperfile")){
			p->singlerefperfile = 1;
			subtract = 1;
		} else if ( args_match( argv[i], "-i", "--input-encoding" ) ) {
			args_encoding( *argc, argv, i, &(p->charsetin),
					&(p->utf8in), progname );
			p->charsetin_src = BIBL_SRC_USER;
			subtract = 2;
		} else if ( args_match( argv[i], "-o", "--output-encoding" )){
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
main(int argc, char *argv[])
{
	FILE *fp;
	param p;
	bibl b;
	int err, i;

	bibl_init( &b );
	bibl_initparams( &p, BIBL_MODSIN, BIBL_ISIOUT );
	process_args( &argc, argv, &p );

	if ( argc==1 ) {
		err = bibl_read( &b, stdin, "stdin", BIBL_MODSIN, &p );
		if ( err ) bibl_reporterr( err );
	} else {
		for ( i=1; i<argc; ++i ) {
			fp = fopen( argv[i], "r" );
			if ( fp ) {
				err = bibl_read(&b,fp,argv[i],BIBL_MODSIN,&p);
				fclose( fp );
				if ( err ) bibl_reporterr( err );
			} else {
				fprintf( stderr, "%s: cannot open %s\n",
					progname, argv[i] );
			}
		}
	}
	bibl_write( &b, stdout, BIBL_ISIOUT, &p );
	fflush( stdout );

	fprintf( stderr, "%s: Processed %ld references.\n", progname, b.nrefs );
	fflush( stderr );

	bibl_free( &b );

	return EXIT_SUCCESS;
}


