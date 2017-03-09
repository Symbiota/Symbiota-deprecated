The following is a walk-through in configuring Apache SOLR to work with your Symbiota installation.
SOLR support in Symbiota was developed using Apache SOLR 6.2.1. Additional configurations may be required
if you are using an older version of SOLR.

Steps for configuring Apache SOLR:

1) Download Apache SOLR from http://lucene.apache.org/solr/ and install on your server. Once installed and
    started, the SOLR admin panel can be accessed at http://localhost:8983/solr/. Do not start SOLR until step 6.

2) Create a directory named lib in [SOLR BASE DIRECTORY]/contrib/dataimporthandler of your SOLR installation.

3) Download the JDBC driver for MySQL (mysql-connector-java-*.jar) at https://dev.mysql.com/downloads/connector/j/
    and copy this file into [SOLR BASE DIRECTORY]/contrib/dataimporthandler/lib.

4) Download the Microsoft JDBC driver 4.0 for SQL Server (sqljdbc4.jar) at https://www.microsoft.com/en-us/download/details.aspx?displaylang=en&id=11774
    and copy this file into [SOLR BASE DIRECTORY]/contrib/dataimporthandler/lib.

5) Download the JTS Topology Suite driver 1.14.0 (jts-core-1.14.0.jar) at https://repo1.maven.org/maven2/com/vividsolutions/jts-core/1.14.0/
    and copy this file into [SOLR BASE DIRECTORY]/server/solr-webapp/webapp/WEB-INF/lib.

6) Start your SOLR installation and run the following command to create a new SOLR core for your Symbiota installation
    replacing CORE_NAME with the name you wish to use for this core:
    bin/solr create -c CORE_NAME

7) Stop your SOLR installation.

8) You should now have a new directory in [SOLR BASE DIRECTORY]/server/solr named your CORE_NAME. Verify this directory exists
    and edit the file [SOLR BASE DIRECTORY]/server/solr/CORE_NAME/conf/solrconfig.xml:

    *Note paths in the following edits may vary depending on your SOLR installation.

    -After the following line (line 85 on SOLR 6.2.1):

        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-velocity-\d.*\.jar" />

    -Add the following lines matching the directory paths to those in the lines before it:

        <lib dir="${solr.install.dir:../../../..}/contrib/dataimporthandler/lib" regex=".*\.jar" />
        <lib dir="${solr.install.dir:../../../..}/dist/" regex="solr-dataimporthandler-.*\.jar" />

    -In the Request Handlers section (line 726 on SOLR 6.2.1), add the following lines:

        <requestHandler name="/dataimport" class="org.apache.solr.handler.dataimport.DataImportHandler">
            <lst name="defaults">
                <str name="config">data-config.xml</str>
            </lst>
        </requestHandler>

    -Save your edits.

9) Copy the files data-config.xml and schema.xml from [SYMBIOTA BASE DIRECTORY]/config/solr to
    [SOLR BASE DIRECTORY]/server/solr/CORE_NAME/conf.

10) Edit the file [SOLR BASE DIRECTORY]/server/solr/CORE_NAME/conf/data-config.xml:

    -On line 4, change [Database Host Name] to the host name of your Symbiota database.

    -On line 4, change [Database Name] to the name of your Symbiota database.

    -On line 6, change [Database Read Only User Name] to the username of the readonly database connection in the
        [SYMBIOTA BASE DIRECTORY]/config/dbconnection.php file of your Symbiota installation.

    -On line 7, change [Database Read Only Password] to the password of the readonly database connection in the
        [SYMBIOTA BASE DIRECTORY]/config/dbconnection.php file of your Symbiota installation.

    -Save your edits.

11) Start your SOLR installation. Go to the SOLR admin panel at http://localhost:8983/solr/ and select your new Symbiota
    core in the Core Selector drop down. Click on DataImport and then click the Execute button to initiate a full import.
    Note that this step may take a significant amount of time to complete depending on the size of your Symbiota database.
    You can click on the Refresh Status button to refresh the status information of your import. This step can also be
    through the command line.

12) Once the full import is complete, edit the [SYMBIOTA BASE DIRECTORY]/config/symbini.php file of your Symbiota installation:

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
        e.g.: $SOLR_FULL_IMPORT_INTERVAL = 12;

    -Save your edits.

Congratulations! You have now configured your Symbiota installation to work with Apache SOLR! Happy Searching!