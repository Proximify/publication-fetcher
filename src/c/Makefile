POSTFIX=
CC = "CC=cc"
RANLIB=RANLIB="echo Skipping ranlib"
INSTALLDIR=/usr/local/bin

VERSION=3.40
DATE=2/10/08

PROGRAMS=bib2xml ris2xml end2xml endx2xml med2xml isi2xml copac2xml \
	xml2ads xml2bib xml2end xml2isi xml2ris xml2wordbib modsclean

all : FORCE
	cd lib; make -k $(CC) -k $(RANLIB) -k VERSION="$(VERSION)" -k DATE="$(DATE)"; cd ..
	cd bin; make -k $(CC); cd ..

clean: FORCE
	cd lib     ; make clean ; cd ..
	cd bin     ; make clean ; cd ..
	cd test    ; make clean ; cd ..

realclean: FORCE
	cd lib     ; make realclean ; cd ..
	cd bin     ; make realclean ; cd ..
	cd test    ; make realclean ; cd ..
	rm -rf update

test: all FORCE
	cd lib    ; make test; cd ..
	cd bin    ; make test; cd ..

install: all FORCE
	@for p in ${PROGRAMS}; \
		do ( cp bin/$$p ${INSTALLDIR}/$$p ); \
	done

package: all FORCE
	csh -f maketgz.csh ${VERSION} ${POSTFIX}

deb: all FORCE
	csh -f makedeb.csh ${VERSION} ${POSTFIX}

FORCE:
