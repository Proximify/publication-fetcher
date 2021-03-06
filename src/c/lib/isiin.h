/*
 * isiin.h
 * 
 * Copyright (c) Chris Putnam 2004-8
 *
 * Program and source code released under the GPL
 *
 */
#ifndef ISIIN_H
#define ISIIN_H

#include "newstr.h"
#include "fields.h"
#include "reftypes.h"

extern int isiin_readf( FILE *fp, char *buf, int bufsize, int *bufpos, newstr *line, newstr *reference, int *fcharset );
extern int isiin_typef( fields *isiin, char *filename, int nref, variants *all, int nall );
extern void isiin_convertf( fields *isiin, fields *info, int reftype, int verbose, variants *all, int nall );
extern int isiin_processf( fields *isiin, char *p, char *filename, long nref );

extern variants isi_all[];
extern int isi_nall;

#endif

