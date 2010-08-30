ABOUT THIS SOFTWARE
===================

The Symbiota Software Project is building a library of webtools to 
aid biologists in establishing specimen based virtual floras and faunas. This project developed from the realization that complex, information rich biodiversity portals are best built through collaborative efforts between software developers, biologist, wildlife managers, and citizen scientist. The central premise of this open source software project is that through a partnership between software engineers and scientific community, higher quality and more publicly useful biodiversity portals can be built. An open source software framework allows the technicians to create the tools, thus freeing the biologist to concentrate their efforts on the curation of quality datasets. In this manor, we can create something far greater than a single entity is capable of doing on their own.
More information about this project can be accessed through:

http://symbiota.org/


ACKNOWLEDGEMENTS
================ 

Symbiota has been generously funded by the National Science 
Foundation (DBI-0743827) from 15 July 2008 to 30 June 2011 
(Estimated). The Global Institute of Sustainability 
(GIOS) at Arizona State University has also been a major 
supporters of the Symbiota initiative since the very beginning. 
Arizona State University Vascular Plant and Lichen Herbarium have 
been intricately involved in the development from the start. 
Sky Island Alliance and the Arizona-Sonora Desert Museum have both 
been long-term participants in the development of this product.


NOTES
=====


FEATURES
========

* Specimen Search Engine
   * Taxonomic Thesaurus for querying taxonomic synonyms
   * Google Map and Google Earth mapping capabilities
   * Dynamic species list generated from specimens records
* Flora/Fauna Management System
   * Static species list (local floras/faunas) 
   * Group survey species list 
* Interactive Identification Keys
   * Key generation for are species list within system
   * Key generator based on a point locality
* Image Library 


LIMITATIONS
===========

* Only tested on Linux and Window (XP, Vista) operating systems
* Should work with an PHP enabled web server though only 
  tested with Apache HTTP Server


INSTALLATION
============

Please read the INSTALL.txt file for installation instructions.


TROUBLESHOOTING
===============

Activating error logging in your PHP installation is a good idea.
You just need to set the following options in you php.ini file:
log_errors = On
error_log = /some_directory/php.log

If you are getting unexpected blank pages in your browser when running 
scripts, try increasing the memory limit of your PHP instance. Look 
for the option "memory_limit" in your php.ini file. The default value 
is usually 8M. Try setting it to 16M or 32M. 

Another PHP setting you might want to adjust is the socket timeout, 
especially if you are going to work with response structures that take 
a long time to load. Look for the "default_socket_timeout" option in the 
php.ini file.

If you need to submit any bug report, use the following service 
(category "TapirLink"):

https://sourceforge.net/tracker/?group_id=38190&atid=422311


DEVELOPER GUIDELINES
====================

Anyone willing to collaborate is welcome. Please contact the author 
about your plans and for SVN access:
egbot at asu dot edu


The following guidelines are adopted:

* Each class has its own file with the same name.
* Class properties are always manipulated through accessors and mutators.
* Custom library functions that are widely used are also defined inside
  classes and accessed through the :: operator.
* Private and protected methods have an underscore prepended in their name.
* Please give preference to longer and clearer variable, function and class 
  names.

It is recommended to subscribe to the following mailing list to get automatic 
notifications about any changes in code:

http://lists.sourceforge.net/mailman/listinfo/digir-cvs

Also remember that TapirLink code should remain compatible with PHP5
