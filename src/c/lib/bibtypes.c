/*
 * bibtypes.c
 *
 * Copyright (c) Chris Putnam 2003-8
 *
 * Program and source code released under the GPL
 *
 */

#include <stdio.h>
#include "fields.h"
#include "reftypes.h"

/* Entry types from the IEEEtran Bibtex Style + others as observed */

/*
 * Journal Article
 */
static lookups article[] = {
	{ "author",       "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",        "TITLE",     TITLE,  LEVEL_MAIN },
	{ "year",         "PARTYEAR",  SIMPLE, LEVEL_MAIN },
	{ "month",        "PARTMONTH", SIMPLE, LEVEL_MAIN },
	{ "day",          "PARTDAY",   SIMPLE, LEVEL_MAIN },
	{ "volume",       "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "pages",        "PAGES",     PAGES,  LEVEL_MAIN },
	{ "number",       "NUMBER",    SIMPLE, LEVEL_MAIN },
	{ "issue",        "ISSUE",     SIMPLE, LEVEL_MAIN },
	{ "journal",      "TITLE",     TITLE,  LEVEL_HOST },
	{ "publisher",    "PUBLISHER", SIMPLE, LEVEL_HOST },
	{ "address",      "ADDRESS",   SIMPLE, LEVEL_HOST },
	{ "issn",         "ISSN",      SIMPLE, LEVEL_HOST },
	{ "abstract",     "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",     "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "language",     "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",  SIMPLE, LEVEL_MAIN },
	{ "ftp",          "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",          "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",          "DOI",       SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",       SIMPLE, LEVEL_MAIN },
	{ "refnum",       "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",            "TYPE|ARTICLE",           ALWAYS, LEVEL_MAIN },
	{ " ",            "ISSUANCE|continuing",    ALWAYS, LEVEL_HOST },
	{ " ",            "RESOURCE|text",          ALWAYS, LEVEL_MAIN },
	{ " ",            "GENRE|periodical",       ALWAYS, LEVEL_HOST },
	{ " ",            "GENRE|academic journal", ALWAYS, LEVEL_HOST }
};

/* Book */

static lookups book[] = {
	{ "author",       "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",        "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle",    "TITLE",     TITLE,  LEVEL_MAIN },
	{ "series",       "TITLE",     TITLE,  LEVEL_HOST },
	{ "publisher",    "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "address",      "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",       "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",         "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",        "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",          "DAY",       SIMPLE, LEVEL_MAIN },
	{ "volume",       "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "number",       "NUMBER",    SIMPLE, LEVEL_MAIN },
	{ "isbn",         "ISBN",      SIMPLE, LEVEL_MAIN },
	{ "lccn",         "LCCN",      SIMPLE, LEVEL_MAIN },
	{ "edition",      "EDITION",   SIMPLE, LEVEL_MAIN },
	{ "abstract",     "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",     "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "language",     "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",  SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",     SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",       SIMPLE, LEVEL_MAIN },
	{ "ftp",          "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",          "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",          "DOI",       SIMPLE, LEVEL_MAIN },
	{ "refnum",       "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",            "TYPE|BOOK",     ALWAYS, LEVEL_MAIN },
	{ " ",            "RESOURCE|text", ALWAYS, LEVEL_MAIN },
	{ " ",            "ISSUANCE|monographic", ALWAYS, LEVEL_MAIN },
	{ " ",            "GENRE|book",    ALWAYS, LEVEL_MAIN }
};

/* Technical reports */

static lookups report[] = {
	{ "author",       "AUTHOR",       PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",        "TITLE",        TITLE,  LEVEL_MAIN },
	{ "booktitle",    "TITLE",        TITLE,  LEVEL_MAIN },
	{ "editor",       "EDITOR",       PERSON, LEVEL_MAIN },
	{ "publisher",    "PUBLISHER",    SIMPLE, LEVEL_MAIN },
	{ "institution",  "PUBLISHER",    SIMPLE, LEVEL_MAIN },
	{ "address",      "ADDRESS",      SIMPLE, LEVEL_MAIN },
	{ "year",         "YEAR",         SIMPLE, LEVEL_MAIN },
	{ "month",        "MONTH",        SIMPLE, LEVEL_MAIN },
	{ "day",          "DAY",          SIMPLE, LEVEL_MAIN },
	{ "number",       "NUMBER",       SIMPLE, LEVEL_MAIN },
	{ "isbn",         "ISBN",         SIMPLE, LEVEL_MAIN },
	{ "lccn",         "LCCN",         SIMPLE, LEVEL_MAIN },
	{ "abstract",     "ABSTRACT",     SIMPLE, LEVEL_MAIN },
	{ "contents",     "CONTENTS",     SIMPLE, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "doi",          "DOI",          SIMPLE, LEVEL_MAIN },
	{ "ftp",          "URL",          BIBTEX_URL, LEVEL_MAIN },
	{ "url",          "URL",          BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",          BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",          SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "type",      "TYPE",            SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|REPORT",     ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",   ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|report",    ALWAYS, LEVEL_MAIN }
};

static lookups manual[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_MAIN },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_MAIN },
	{ "edition",   "EDITION",   SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|REPORT",       ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",     ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|instruction", ALWAYS, LEVEL_MAIN }
};

/* Part of a book (e.g. chapter or section) */

static lookups inbook[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_HOST },
	{ "chapter",   "TITLE",     TITLE,  LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_HOST },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_HOST },
	{ "series",    "TITLE",     TITLE,  LEVEL_SERIES },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_HOST },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_HOST },
	{ "year",      "YEAR",      SIMPLE, LEVEL_HOST },
	{ "month",     "MONTH",     SIMPLE, LEVEL_HOST },
	{ "day",       "DAY",       SIMPLE, LEVEL_HOST },
	{ "volume",    "VOLUME",    SIMPLE, LEVEL_SERIES },
	{ "number",    "NUMBER",    SIMPLE, LEVEL_SERIES },
	{ "pages",     "PAGES",     PAGES,  LEVEL_HOST },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_HOST },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_HOST },
	{ "edition",   "EDITION",   SIMPLE, LEVEL_HOST },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_HOST },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_HOST },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "type",      "TYPE",      SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|INBOOK",   ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text", ALWAYS, LEVEL_MAIN },
	{ " ",         "ISSUANCE|monographic", ALWAYS, LEVEL_HOST },
	{ " ",         "GENRE|book",    ALWAYS, LEVEL_HOST }
};

/* References of papers in conference proceedings */

static lookups inproceedings[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_HOST },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_HOST },
	{ "series",    "TITLE",     TITLE,  LEVEL_SERIES },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_HOST },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_HOST },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_HOST },
	{ "year",      "PARTYEAR",  SIMPLE, LEVEL_MAIN },
	{ "month",     "PARTMONTH", SIMPLE, LEVEL_MAIN },
	{ "day",       "PARTDAY",   SIMPLE, LEVEL_MAIN },
	{ "volume",    "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "number",    "NUMBER",    SIMPLE, LEVEL_MAIN },
	{ "pages",     "PAGES",     PAGES,  LEVEL_MAIN },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_HOST },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_HOST },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_HOST },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "paper",     "PAPER",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_HOST },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "type",      "TYPE",      SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|INPROCEEDINGS",  ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",       ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|conference publication",   ALWAYS, LEVEL_HOST }
};

/* Used for a section of a book with its own title */

static lookups incollection[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_HOST },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "chapter",   "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_HOST },
	{ "series",    "TITLE",     TITLE,  LEVEL_SERIES },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_HOST },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_HOST },
	{ "year",      "YEAR",      SIMPLE, LEVEL_HOST },
	{ "month",     "MONTH",     SIMPLE, LEVEL_HOST },
	{ "day",       "DAY",       SIMPLE, LEVEL_HOST },
	{ "volume",    "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "number",    "NUMBER",    SIMPLE, LEVEL_MAIN },
	{ "pages",     "PAGES",     PAGES,  LEVEL_MAIN },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_HOST },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_HOST },
	{ "edition",   "EDITION",   SIMPLE, LEVEL_HOST },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_HOST },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "type",      "TYPE",      SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_HOST },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|INCOLLECTION",   ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text", ALWAYS, LEVEL_MAIN },
	{ " ",         "ISSUANCE|monographic", ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|collection",    ALWAYS, LEVEL_HOST }
};

/* Proceedings */

static lookups proceedings[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "series",    "TITLE",     TITLE,  LEVEL_HOST },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "volume",    "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "number",    "NUMBER",    SIMPLE, LEVEL_MAIN },
	{ "pages",     "PAGES",     PAGES,  LEVEL_MAIN },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_MAIN },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|PROCEEDINGS",    ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",       ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|conference publication",   ALWAYS, LEVEL_MAIN }
};

static lookups phds[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "school",    "DEGREEGRANTOR:ASIS",SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL" ,   BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "type",         "TYPE",         SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|THESIS",   ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text", ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|theses",  ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|Ph.D. thesis",  ALWAYS, LEVEL_MAIN }
};

static lookups masters[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "school",    "DEGREEGRANTOR:ASIS",SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "type",         "TYPE",         SIMPLE, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|THESIS",   ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text", ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|theses",  ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|Masters thesis",  ALWAYS, LEVEL_MAIN }
};

/* Unpublished */

static lookups unpublished[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|BOOK",       ALWAYS, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",   ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|unpublished",      ALWAYS, LEVEL_MAIN }
};

/*
 * For Published Standards
 */
static lookups standard[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "institution", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "isbn",      "ISBN",      SIMPLE, LEVEL_MAIN },
	{ "lccn",      "LCCN",      SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "language",     "LANGUAGE",     SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "type",      "TYPE",      SIMPLE, LEVEL_MAIN },
	{ "number",    "NUMBER",      TITLE,  LEVEL_MAIN },
	{ "revision",  "REVISION",    TITLE,  LEVEL_MAIN },
	{ "location",     "LOCATION",     SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",        SIMPLE, LEVEL_MAIN },
	{ "key",          "BIBKEY",          SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",   ALWAYS, LEVEL_MAIN },
	{ " ",         "TYPE|STANDARD",    ALWAYS, LEVEL_MAIN }
};

/*
 * For Journals and Magazines
 */
static lookups periodical[] = {
	{ "title",        "TITLE",       TITLE,  LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "series",       "TITLE",       TITLE,  LEVEL_HOST },
	{ "publisher",    "PUBLISHER",   SIMPLE, LEVEL_MAIN },
	{ "address",      "ADDRESS",     SIMPLE, LEVEL_MAIN },
	{ "editor",       "EDITOR",      PERSON, LEVEL_MAIN },
	{ "year",         "YEAR",        SIMPLE, LEVEL_MAIN },
	{ "month",        "MONTH",       SIMPLE, LEVEL_MAIN },
	{ "day",          "DAY",         SIMPLE, LEVEL_MAIN },
	{ "issn",         "ISSN",        SIMPLE, LEVEL_MAIN },
	{ "abstract",     "ABSTRACT",    SIMPLE, LEVEL_MAIN },
	{ "volume",       "VOLUME",      TITLE,  LEVEL_MAIN },
	{ "number",       "NUMBER",      TITLE,  LEVEL_MAIN },
	{ "contents",     "CONTENTS",    SIMPLE, LEVEL_MAIN },
	{ "ftp",          "URL",         BIBTEX_URL, LEVEL_MAIN },
	{ "doi",          "DOI",         SIMPLE, LEVEL_MAIN },
	{ "url",          "URL",         BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",         BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",       "REFNUM",      SIMPLE, LEVEL_MAIN },
	{ "language",     "LANGUAGE",    SIMPLE, LEVEL_MAIN },
	{ "note",         "NOTES",       SIMPLE, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",            "RESOURCE|text",   ALWAYS, LEVEL_MAIN },
	{ " ",            "GENRE|periodical",ALWAYS, LEVEL_MAIN }
};

/*
 * For Patent
 */
static lookups patent[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "assignee",  "ASSIGNEE",  PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN }, /* date granted */
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "yearfiled", "YEAR",      SIMPLE, LEVEL_MAIN }, /* date filed */
	{ "monthfiled","MONTH",     SIMPLE, LEVEL_MAIN },
	{ "dayfiled",  "DAY",       SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "language",  "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "location",  "LOCATION",  SIMPLE, LEVEL_MAIN },
	{ "nationality", "NATIONALITY", SIMPLE, LEVEL_MAIN },
	{ "note",      "NOTES",     SIMPLE, LEVEL_MAIN },
	{ "key",       "BIBKEY",       SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "type",       "TYPE",     SIMPLE, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "RESOURCE|text",   ALWAYS, LEVEL_MAIN },
	{ " ",         "TYPE|PATENT",    ALWAYS, LEVEL_MAIN },
};

/*
 * Electronic Source
 */
static lookups electronic[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "volume",    "VOLUME",    SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "location",  "LOCATION",  SIMPLE, LEVEL_MAIN },
	{ "note",      "NOTES",     SIMPLE, LEVEL_MAIN },
	{ "key",       "BIBKEY",       SIMPLE, LEVEL_MAIN },
	{ "language",  "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "keywords",     "KEYWORD",   SIMPLE, LEVEL_MAIN },
        { " ",  "RESOURCE|software, multimedia",    ALWAYS, LEVEL_MAIN },
	{ " ",         "GENRE|electronic",       ALWAYS, LEVEL_MAIN },
};

static lookups misc[] = {
	{ "author",    "AUTHOR",    PERSON, LEVEL_MAIN },
	{ "translator",   "TRANSLATOR",PERSON, LEVEL_MAIN },
	{ "title",     "TITLE",     TITLE,  LEVEL_MAIN },
	{ "booktitle", "TITLE",     TITLE,  LEVEL_MAIN },
	{ "publisher", "PUBLISHER", SIMPLE, LEVEL_MAIN },
	{ "organization", "AUTHOR:CORP", SIMPLE, LEVEL_MAIN },
	{ "pages",     "PAGES",     PAGES,  LEVEL_MAIN },
	{ "address",   "ADDRESS",   SIMPLE, LEVEL_MAIN },
	{ "editor",    "EDITOR",    PERSON, LEVEL_MAIN },
	{ "year",      "YEAR",      SIMPLE, LEVEL_MAIN },
	{ "month",     "MONTH",     SIMPLE, LEVEL_MAIN },
	{ "day",       "DAY",       SIMPLE, LEVEL_MAIN },
	{ "abstract",  "ABSTRACT",  SIMPLE, LEVEL_MAIN },
	{ "contents",  "CONTENTS",  SIMPLE, LEVEL_MAIN },
	{ "ftp",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "doi",       "DOI",       SIMPLE, LEVEL_MAIN },
	{ "url",       "URL",       BIBTEX_URL, LEVEL_MAIN },
	{ "howpublished", "URL",    BIBTEX_URL, LEVEL_MAIN },
	{ "refnum",    "REFNUM",    SIMPLE, LEVEL_MAIN },
	{ "crossref",     "CROSSREF",  SIMPLE, LEVEL_MAIN },
	{ "location",  "LOCATION",  SIMPLE, LEVEL_MAIN },
	{ "note",      "NOTES",     SIMPLE, LEVEL_MAIN },
	{ "key",       "BIBKEY",       SIMPLE, LEVEL_MAIN },
	{ "language",  "LANGUAGE",  SIMPLE, LEVEL_MAIN },
	{ "keywords",  "KEYWORD",   SIMPLE, LEVEL_MAIN },
	{ " ",         "TYPE|MISC",       ALWAYS, LEVEL_MAIN },
};

variants bibtex_all[] = {
	{ "article", &(article[0]), sizeof(article)/sizeof(lookups)},
	{ "book", &(book[0]), sizeof(book)/sizeof(lookups)},
	{ "booklet", &(book[0]), sizeof(book)/sizeof(lookups)},
	{ "inbook", &(inbook[0]), sizeof(inbook)/sizeof(lookups)},
	{ "proceedings", &(proceedings[0]), sizeof(proceedings)/sizeof(lookups)},
	{ "inproceedings", &(inproceedings[0]), sizeof(inproceedings)/sizeof(lookups)},
	{ "inconference", &(inproceedings[0]), sizeof(inproceedings)/sizeof(lookups)},
	{ "incollection", &(incollection[0]), sizeof(incollection)/sizeof(lookups)},
	{ "phdthesis", &(phds[0]), sizeof(phds)/sizeof(lookups)},
	{ "mastersthesis", &(masters[0]), sizeof(masters)/sizeof(lookups)},
	{ "report", &(report[0]), sizeof(report)/sizeof(lookups)},
	{ "techreport", &(report[0]), sizeof(report)/sizeof(lookups)},
	{ "manual", &(manual[0]), sizeof(manual)/sizeof(lookups)},
	{ "unpublished", &(unpublished[0]), sizeof(unpublished)/sizeof(lookups)},
	{ "periodical", &(periodical[0]), sizeof(periodical)/sizeof(lookups)},
	{ "electronic", &(electronic[0]), sizeof(electronic)/sizeof(lookups)},
	{ "patent", &(patent[0]), sizeof(patent)/sizeof(lookups)},
	{ "standard", &(standard[0]), sizeof(standard)/sizeof(lookups)},
	{ "misc", &(misc[0]), sizeof(misc)/sizeof(lookups)}
};

int bibtex_nall = sizeof( bibtex_all ) / sizeof( variants );
