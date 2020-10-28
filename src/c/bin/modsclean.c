/*
 * modsclean.c
 *
 */
#include <stdio.h>
#include <stdlib.h>
#include "bibutils.h"

char	progname[] = "modsclean";

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
	bibl_initparams( &p, BIBL_MODSIN, BIBL_MODSOUT );
	if ( argc<2 ) {
		err = bibl_read( &b, stdin, "stdin", BIBL_MODSIN, &p );
		if ( err ) bibl_reporterr( err );
	} else {
		for ( i=1; i<argc; ++i ) {
			fp = fopen( argv[i], "r" );
			if ( fp ) {
				err = bibl_read( &b, fp, argv[i], BIBL_MODSIN, 
						&p );
				if ( err ) bibl_reporterr( err );
				fclose( fp );
			}
		}
	}
	bibl_write( &b, stdout, BIBL_MODSOUT, &p );
	fprintf( stderr, "%s: Processed %ld references.\n", progname, b.nrefs );
	bibl_free( &b );
	return EXIT_SUCCESS;
}
