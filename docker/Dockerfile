FROM debian:stable-slim

ENV DEBIAN_FRONTEND noninteractive

# Don't change these
ENV APACHE_RUN_DIR "/var/run/apache2"
ENV APACHE_PID_FILE "/var/run/apache.pid"
ENV APACHE_RUN_USER "www-data"
ENV APACHE_RUN_GROUP "www-data"
ENV APACHE_LOG_DIR "/var/log/apache2"

ENV SYMBIOTA_ROOT /var/www/symbiota

# Install dependencies
RUN apt-get update && apt-get dist-upgrade -y
RUN apt-get install -y              \
    apache2                         \
    git                             \
    imagemagick                     \
    libapache2-mod-php              \
    libyaml-0-2                     \
    php-gd                          \
    php-mbstring                    \
    php-mysql                       \
    php-solr                        \
    php-yaml                        \
    php-zip                         \
    tesseract-ocr

# Get the source & clean up
RUN git clone --depth 1 https://github.com/Symbiota/Symbiota.git ${SYMBIOTA_ROOT} &&    \
    apt-get remove --purge git -y &&                                                    \
    apt-get autoremove --purge -y &&                                                    \
    rm -rf /var/lib/apt/lists/*

# Enable php for apache
RUN a2enmod php7.0

# Log apache & php output to console
RUN ln -sf /dev/stdout /var/log/apache2/access.log &&   \
    ln -sf /dev/stderr /var/log/apache2/error.log

# Copy over the yml --> php config generators
COPY config/symbini.php ${SYMBIOTA_ROOT}/config/
COPY config/dbconnection.php ${SYMBIOTA_ROOT}/config/

COPY config/index.php ${SYMBIOTA_ROOT}/
COPY config/header.php ${SYMBIOTA_ROOT}/
COPY config/footer.php ${SYMBIOTA_ROOT}/

RUN /bin/bash -c 'chmod +x ${SYMBIOTA_ROOT}/config/{symbini,dbconnection}.php' &&   \
    /bin/bash -c 'chmod +x ${SYMBIOTA_ROOT}/{index,header,footer}.php'

# Edit these for custom style
RUN cp ${SYMBIOTA_ROOT}/css/main_template.css ${SYMBIOTA_ROOT}/css/main.css &&  \
    cp ${SYMBIOTA_ROOT}/css/speciesprofile_template.css ${SYMBIOTA_ROOT}/css/speciesprofile.css

# Mount /usr/local/etc/symbiota outside the container to configure symbiota
COPY config/yml/* /usr/local/etc/symbiota/
VOLUME /usr/local/etc/symbiota

# Copy apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Create image/iplant upload directory; Hard coded this into path the config
# for greater simplicity
RUN mkdir -p ${SYMBIOTA_ROOT}/content/image/iplant

# Configure web server permissions
RUN chown -R www-data:www-data ${SYMBIOTA_ROOT}/temp &&                     \
    chown -R www-data:www-data ${SYMBIOTA_ROOT}/webservices/dwc/rss.xml &&  \
    chown -R www-data:www-data ${SYMBIOTA_ROOT}/content/dwca &&             \
    chown -R www-data:www-data ${SYMBIOTA_ROOT}/content/image &&            \
    chown -R www-data:www-data ${SYMBIOTA_ROOT}/content/logs &&             \
    chmod -R +r ${SYMBIOTA_ROOT}

# Mount for further configuration
VOLUME ${SYMBIOTA_ROOT}

# TODO: SSL Support
EXPOSE 80
EXPOSE 443

WORKDIR ${SYMBIOTA_ROOT}
CMD ["apache2", "-DFOREGROUND"]
