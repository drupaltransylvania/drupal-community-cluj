FROM ubuntu:14.04
MAINTAINER Daniel Popdan <popdan.daniel@gmail.com>

# Specifies it is not used interactively.
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get -yq install \
    software-properties-common \
    python-software-properties

RUN add-apt-repository ppa:ondrej/php

# Installs base packages.
RUN apt-get update && \
        apt-get --force-yes -yq install \
        apache2 \
        php5.6 \
        php5.6-dev \
        php5.6-xdebug \
        php5.6-gd \
        php5.6-mysql \
        php5.6-cli \
        git \
        pkg-config \
        libmemcached-dev \
        mysql-client \
        curl \
        nodejs \
        npm \
        php5.6-curl \
        php5.6-dom \
        php5.6-bcmath \
        memcached \
        php-memcached

# Installs node modules.
RUN npm install -g \
	  eslint \
	  csslint

# Symlinks node directory.
RUN ln -s /usr/bin/nodejs /usr/bin/node

# Donwload and install composer.
RUN curl -sS https://getcomposer.org/installer \
 	  | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' > ~/.bashrc \
		&& . ~/.bashrc \
		&& composer global require drush/drush:~8

RUN composer global install \
		&& composer global update

# Sets opcache configurations.
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=0'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /etc/php/5.6/apache2/conf.d/opcache-recommended.ini

# Sets extra php.ini settings.
RUN { \
		echo 'date.timezone = Europe/Paris'; \
		echo 'upload_max_filesize = 256M'; \
		echo 'post_max_size = 256M'; \
		echo 'xdebug.max_nesting_level = 256'; \
	} > /etc/php/5.6/apache2/conf.d/mvc.ini

# Add port for Testing.
RUN echo "Listen 6001" >> /etc/apache2/ports.conf

# Adds packages.
env APACHE_RUN_USER    www-data
env APACHE_RUN_GROUP   www-data
env APACHE_PID_FILE    /var/run/apache2.pid
env APACHE_RUN_DIR     /var/run/apache2
env APACHE_LOCK_DIR    /var/lock/apache2
env APACHE_LOG_DIR     /var/log/apache2

# Enables apache modules.
RUN a2enmod rewrite

# Adds memcached.
#RUN  git clone https://github.com/php-memcached-dev/php-memcached.git \
#  && cd php-memcached \
#  && phpize \
#  && ./configure --disable-memcached-sasl \
#  && make \
#  && make install

# add files
ADD . /var/www/html

# Updates the default apache site with the config we created.
ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

RUN curl https://drupalconsole.com/installer -L -o drupal.phar \
	&& php -r "readfile('https://drupalconsole.com/installer');" > drupal.phar \
	&& mv drupal.phar /usr/local/bin/drupal \
	&& chmod +x /usr/local/bin/drupal \
	&& drupal init --override


# By default, simply starts apache.
CMD /usr/sbin/apache2ctl -D FOREGROUND