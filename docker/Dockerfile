FROM debian:stable-slim

ENV DEBIAN_FRONTEND noninteractive

# Don't change these
ENV APACHE_RUN_DIR "/var/run/apache2"
ENV APACHE_PID_FILE "/var/run/apache.pid"
ENV APACHE_RUN_USER "www-data"
ENV APACHE_RUN_GROUP "www-data"
ENV APACHE_LOG_DIR "/var/log/apache2"

ENV PATH_SYMBIOTA /var/www/symbiota

# Install dependencies
RUN apt-get update && apt-get dist-upgrade -y
RUN apt-get install -y  \
    apache2             \
    git                 \
    imagemagick         \
    inotify-tools       \
    libapache2-mod-php  \
    libtesseract-dev    \
    php-gd              \
    php-mbstring        \
    php-mysql           \
    php-solr            \
    php-zip             \
    python3             \
    python3-yaml        \
    tesseract-ocr

# Clean up
RUN apt-get autoremove --purge -y  && rm -rf /var/lib/apt/lists/*

# Enable php for apache
RUN a2enmod php7.0

# Get the source
RUN git clone --depth 1 https://github.com/Symbiota/Symbiota.git ${PATH_SYMBIOTA}
WORKDIR ${PATH_SYMBIOTA}

COPY config/configure-symbiota.py /usr/local/bin/configure-symbiota
RUN chmod +x /usr/local/bin/configure-symbiota

# Mount /usr/local/etc/symbiota outside the container to configure symbiota
RUN mkdir /usr/local/etc/symbiota

COPY config/default_config.yml /usr/local/etc/symbiota/symbiota.yml
COPY config/php /usr/local/etc/symbiota/php
COPY config/css /usr/local/etc/symbiota/css

VOLUME /usr/local/etc/symbiota

# Copy apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Create image/iplant upload directory
RUN mkdir -p ${PATH_SYMBIOTA}/content/image/iplant

# Configure web server permissions
RUN chown -R www-data:www-data ${PATH_SYMBIOTA}/temp
RUN chown -R www-data:www-data ${PATH_SYMBIOTA}/webservices/dwc/rss.xml
RUN chown -R www-data:www-data ${PATH_SYMBIOTA}/content/dwca
RUN chown -R www-data:www-data ${PATH_SYMBIOTA}/content/image
RUN chown -R www-data:www-data ${PATH_SYMBIOTA}/content/logs
RUN chmod -R +r ${PATH_SYMBIOTA}

# Configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Mount for further configuration
VOLUME ${PATH_SYMBIOTA}

# TODO: SSL Support
EXPOSE 80
EXPOSE 443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2", "-DFOREGROUND"]
