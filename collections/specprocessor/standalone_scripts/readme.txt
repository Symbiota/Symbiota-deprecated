Image Batch Processing 

Required files listed below can be left within the full Symbiota file structure (e.g. do a full SVN checkout) or files can be pulled out and copied over into a single folder

Required files

* ImageBatchConf.php
	* Create by renaming ImageBatchConf_template.php
* ImageBatchConnectionFactory.php
	* Create by renaming ImageBatchConnectionFactory_template.php
	* If file is obmitted or connection variables are not set, the default /trunk/config/dbconnection.php file will be used
* ImageBatchHandler.php
	* This is the file that you call to trigger batch processing
* ImageBatchProcessor.php
	* Located in /trunk/classes/
* SpecProcessorGPI.php
	* Located in /trunk/classes/
	* Required for parsing XML batch files that follow the ALUKA/GPI schema.
* SpecProcessorNEVP.php
	* Located in /trunk/classes/
	* Required for parsing RDF/XML batch files containing new occurrence annotations (as used by NEVP).

