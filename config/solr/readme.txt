The following is a walk-through in configuring Apache SOLR to work with your Symbiota installation.
SOLR support in Symbiota was developed using Apache SOLR 6.2.1. Additional configurations may be required
if you are using an older version of SOLR.

Steps for installing and configuring Apache SOLR:

1) Download Apache SOLR from http://lucene.apache.org/solr/ and install on your server.

    **QUICK START INSTRUCTIONS FOR LINUX SYSTEMS - REPLACE [SOLR_VER] WITH THE VERSION OF SOLR YOU ARE INSTALLING (eg. 6.2.1)**

    *INSTALL JAVA 8 (IF NOT ALREADY INSTALLED)
    $ sudo add-apt-repository ppa:webupd8team/java
    $ sudo apt-get update
    $ sudo apt-get install oracle-java8-installer
    $ sudo apt-get install oracle-java8-set-default

    *INSTALL SOLR
    $ cd /tmp
    $ wget http://www.us.apache.org/dist/lucene/solr/[SOLR_VER]/solr-[SOLR_VER].tgz
    $ tar xzf solr-[SOLR_VER].tgz solr-[SOLR_VER]/bin/install_solr_service.sh --strip-components=2
    $ sudo ./install_solr_service.sh solr-[SOLR_VER].tgz

    *STOP SOLR
    $ sudo /etc/init.d/solr stop

    **THESE QUICK START INSTRUCTIONS WILL SET THE [SOLR BASE DIRECTORY] TO /opt/solr
    **THESE QUICK START INSTRUCTIONS WILL SET THE [SOLR DATA DIRECTORY] TO /var/solr/data

2) Create a directory named lib in [SOLR BASE DIRECTORY]/contrib/dataimporthandler of your SOLR installation.

3) Download the JDBC driver for MySQL (mysql-connector-java-*.jar) at https://dev.mysql.com/downloads/connector/j/
    and copy this file into [SOLR BASE DIRECTORY]/contrib/dataimporthandler/lib.

4) Download the JTS Topology Suite driver 1.14.0 (jts-core-1.14.0.jar) at https://repo1.maven.org/maven2/com/vividsolutions/jts-core/1.14.0/
    and copy this file into [SOLR BASE DIRECTORY]/server/solr-webapp/webapp/WEB-INF/lib.

5) Start your SOLR installation and run the following command to create a new SOLR core for your Symbiota installation
    replacing [CORE_NAME] with the name you wish to use for this core:

    **QUICK START INSTRUCTIONS FOR LINUX SYSTEMS**

    *START SOLR
    $ sudo /etc/init.d/solr start

    *CREATE A NEW SOLR CORE FOR YOUR SYMBIOTA INSTALLATION
    $ sudo su - solr -c "/opt/solr/bin/solr create -c [CORE_NAME]"

6) Stop your SOLR installation.

7) You should now have a new directory in [SOLR DATA DIRECTORY] named your [CORE_NAME]. Verify this directory exists
    and edit the file [SOLR DATA DIRECTORY]/[CORE_NAME]/conf/solrconfig.xml:

    *Note paths in the following edits may vary depending on your SOLR installation.

    -Edit the following lines:

        <lib dir="${solr.install.dir:../../../..}/contrib/extraction/lib" regex=".*\.jar" />
        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-cell-\d.*\.jar" />

        <lib dir="${solr.install.dir:../../../..}/contrib/clustering/lib/" regex=".*\.jar" />
        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-clustering-\d.*\.jar" />

        <lib dir="${solr.install.dir:../../../..}/contrib/langid/lib/" regex=".*\.jar" />
        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-langid-\d.*\.jar" />

        <lib dir="${solr.install.dir:../../../..}/contrib/velocity/lib" regex=".*\.jar" />
        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-velocity-\d.*\.jar" />

    -To the following, replacing [SOLR BASE DIRECTORY] with the base path of your SOLR installation:

        <lib dir="[SOLR BASE DIRECTORY]/contrib/extraction/lib" regex=".*\.jar" />
        <lib dir="[SOLR BASE DIRECTORY]/dist/" regex="solr-cell-\d.*\.jar" />

        <lib dir="[SOLR BASE DIRECTORY]/contrib/clustering/lib/" regex=".*\.jar" />
        <lib dir="[SOLR BASE DIRECTORY]/dist/" regex="solr-clustering-\d.*\.jar" />

        <lib dir="[SOLR BASE DIRECTORY]/contrib/langid/lib/" regex=".*\.jar" />
        <lib dir="[SOLR BASE DIRECTORY]/dist/" regex="solr-langid-\d.*\.jar" />

        <lib dir="[SOLR BASE DIRECTORY]/contrib/velocity/lib" regex=".*\.jar" />
        <lib dir="[SOLR BASE DIRECTORY]/dist/" regex="solr-velocity-\d.*\.jar" />

    -Below the last line, Add the following lines, replacing [SOLR BASE DIRECTORY] with the base path of your SOLR installation:

        <lib dir="[SOLR BASE DIRECTORY]/contrib/dataimporthandler/lib" regex=".*\.jar" />
        <lib dir="[SOLR BASE DIRECTORY]/dist/" regex="solr-dataimporthandler-.*\.jar" />

    -In the Request Handlers section, add the following lines:

        <requestHandler name="/dataimport" class="org.apache.solr.handler.dataimport.DataImportHandler">
            <lst name="defaults">
                <str name="config">data-config.xml</str>
            </lst>
        </requestHandler>

    -Save your edits.

8) Delete the file managed-schema from the [SOLR DATA DIRECTORY]/[CORE_NAME]/conf directory.

    **QUICK START INSTRUCTIONS FOR LINUX SYSTEMS**

    *DELETE managed-schema FILE
    $ sudo rm [SOLR DATA DIRECTORY]/[CORE_NAME]/conf/managed-schema

9) Copy the files data-config.xml and schema.xml from [SYMBIOTA BASE DIRECTORY]/config/solr to
    [SOLR DATA DIRECTORY]/[CORE_NAME]/conf.

10) On Linux systems, set the owner and group of these two files to solr.

    **QUICK START INSTRUCTIONS FOR LINUX SYSTEMS**

    *SET OWNER AND GROUP TO solr
    $ sudo chown solr:solr [SOLR DATA DIRECTORY]/[CORE_NAME]/conf/data-config.xml
    $ sudo chown solr:solr [SOLR DATA DIRECTORY]/[CORE_NAME]/conf/schema.xml

11) Edit the file [SOLR DATA DIRECTORY]/[CORE_NAME]/conf/data-config.xml:

    -On line 4, change [Database Host Name] to the host name of your Symbiota database.

    -On line 4, change [Database Name] to the name of your Symbiota database.

    -On line 6, change [Database Read Only User Name] to the username of the readonly database connection in the
        [SYMBIOTA BASE DIRECTORY]/config/dbconnection.php file of your Symbiota installation.

    -On line 7, change [Database Read Only Password] to the password of the readonly database connection in the
        [SYMBIOTA BASE DIRECTORY]/config/dbconnection.php file of your Symbiota installation.

    -Save your edits.

12) Start your SOLR installation. Go to the SOLR admin panel at http://localhost:8983/solr/ and select your new Symbiota
    core in the Core Selector drop down. Click on DataImport and then click the Execute button to initiate a full import.
    Note that this step may take a significant amount of time to complete depending on the size of your Symbiota database.
    You can click on the Refresh Status button to refresh the status information of your import.

    **QUICK START INSTRUCTIONS FOR LINUX SYSTEMS**

    *INITIATE A FULL IMPORT
    $ curl "http://localhost:8983/solr/[CORE_NAME]/dataimport?command=full-import"

13) Once the full import is complete, edit the [SYMBIOTA BASE DIRECTORY]/config/symbini.php file of your Symbiota installation:

    -Either locate or add the line:
        $SOLR_URL = '';

    -Set the value of this line to the url of your Symbiota SOLR core.
        e.g.: $SOLR_URL = 'http://localhost:8983/solr/symbseinet';

    -Either locate or add the line:
        $SOLR_FULL_IMPORT_INTERVAL = 0;

    -Change the value of this line to the hour interval you wish to have between whcih your Symbiota installation
        will initiate a full import refresh of your SOLR core. Records added or edited will automatically be updated
        in your SOLR core without the need for a full import. Deleted records or images will only be updated in the core
        through a full import however, so if your portal has frequent record or image deletions, set this to a lower setting,
        if not, set this value to a higher setting.
        e.g.: $SOLR_FULL_IMPORT_INTERVAL = 24;

    -Save your edits.

Congratulations! You have now configured your Symbiota installation to work with Apache SOLR! Happy Searching!