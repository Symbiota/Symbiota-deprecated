# [OregonFlora](https://symbiota.oregonflora.org/portal/)

A [Symbiota](http://symbiota.org) portal focusing on the vascular plants of Oregon. For the Symbiota README, see 
[here](https://github.com/Symbiota/Symbiota/blob/master/docs/README.txt). In addition to the basic Symbiota environment,
**PHP >= 7** is required for this project, along with the **php-apcu** package.

The site features content that diverges significantly from the Symbiota project, while still adhering to the 
Symbiota database structure and maintaining core Symbiota features.
For the site content that differs from Symbiota, which includes the home page, garden page, and taxonomic profiles,
OregonFlora development differs in the following ways: 
   - [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) is used to access the database, providing 
     an asynchronous JSON API. This is done mainly to decouple the PHP-based server-side code from the front end.
     [Composer](https://getcomposer.org/) is used as the build system.
   - For the front end, [ReactJS](https://reactjs.org) and [Less](http://lesscss.org/) are used. 
   [NodeJS](https://nodejs.org/) is used as the build system.
   - Wherever possible, PHP backend code is separated from the HTML/CSS/JS frontend. This backend code exposes data
   to the frontend using asynchronous JSON. For example:
        - Site's navbar is in [js/react/src](./js/react/src/header) and consumes data exposed by 
        [webservices/autofillsearch.php](./webservices/autofillsearch.php)
        - [Garden page](https://oregonflora.org/garden/index.php) frontend is in
            [js/react/src/garden](./js/react/src/garden) and consumes data exposed by the backend in 
            [garden/rpc/api.php](./garden/rpc/api.php)
        - [Taxa page](https://oregonflora.org/taxa/search.php?search=cat) frontend is in
            [js/react/src/taxa](./js/react/src/taxa) and consumes data exposed by the backend in 
            [taxa/rpc/api.php](./taxa/rpc/api.php) 
   - These changes have made much of the original Symbiota code unneeded, but it has been left in wherever possible
   for compatibility, as not all code React/Doctrine based (yet).

### To build the back end:
1. Follow the [Symbiota installation instructions](https://github.com/Symbiota/Symbiota/blob/master/docs/INSTALL.txt) 
for Apache, PHP, and MariaDB/MySQL
2. Install Composer for PHP
3. Run the following in the repository root to install the PHP dependencies: `composer install`
4. Run the following in the repository root to generate Doctine's proxy classes `doctrine orm:generate-proxies`. In a
development environment, you can set IS_DEV to true in [symbini.php](./config/symbini_template.php) to do this automatically
every time you make changes to the Doctrine-based PHP code.

### To build the front end:
Install NodeJS and run the following from [js/react](./js/react)
1. Install the NodeJS dependences: `npm install`
2. Build the React- and Less-based pages: `npm run build`


For a development server that watches for changes in .js/.jsx/.less files and automatically rebuilds them: `npm run devstart`
from the [js/react](./js/react) directory.
