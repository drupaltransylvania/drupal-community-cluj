FROM php:7.1.6-apache
MAINTAINER Daniel Popdan <popdan.daniel@gmail.com>

# Enables apache modules.
RUN a2enmod rewrite

# Updates the default apache site with the config we created.
ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

CMD ["apache2-foreground"]
