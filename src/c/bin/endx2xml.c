/*
 * endx2xml.c
 * 
 * Copyright (c) Chris Putnam 2006
 *
 * Source code and program released under the GPL
 *
 */
#include <stdio.h>
#include <stdlib.h>
#include "bibutils.h"
#include "bibprogs.h"

char progname[] = "endx2xml";
char help1[] =  "Converts a Endnote XML file (v8 or later) into MODS XML\n\n";
char help2[] = "endnotexml_file";

lists asis  = { 0, 0, NULL };
lists corps = { 0, 0, NULL };

int
main( int argc, char *argv[] )
{
	FILE *fp;
	param p;
	bibl b;
	int err, i;
	bibl_init( &b );
	bibl_initparams( &p, BIBL_ENDNOTEXMLIN, BIBL_MODSOUT );
	tomods_processargs( &argc, argv, &p, progname, help1, help2 );
	if ( argc<2 ) {
		err = bibl_read( &b, stdin, "stdin", BIBL_ENDNOTEXMLIN, & p );
		if ( err ) bibl_reporterr( err );
	} else {
		for ( i=1; i<argc; ++i ) {
			fp = fopen( argv[i], "r" );
			if ( fp ) {
				err = bibl_read( &b, fp, argv[i], BIBL_ENDNOTEXMLIN, &p );
				if ( err ) bibl_reporterr( err );
				fclose( fp );
			}
		}
	}
	bibl_write( &b, stdout, BIBL_MODSOUT, &p );
	fflush( stdout );
	fprintf( stderr, "%s: Processed %ld references.\n", progname, b.nrefs );
	bibl_free( &b );
	return EXIT_SUCCESS;
}
