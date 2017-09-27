# this is to be used as a base image
FROM php:7.1.6-apache

MAINTAINER Oluwafemi Adeosun <oluwafemi.adeosun@andela.com>

RUN apt-get update -y && apt-get install -y openssl zip unzip git vim \
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo mbstring

RUN rm -rf /var/www/html/*; rm -rf /etc/apache2/sites-enabled/*; \
    mkdir -p /etc/apache2/external

# Install oAuth
RUN apt-get update \
	&& apt-get install -y \
	libpcre3 \
	libpcre3-dev \
	php-pear \
	npm \
	&& pecl install oauth \
	&& echo "extension=oauth.so" > /usr/local/etc/php/conf.d/docker-php-ext-oauth.ini

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2

RUN sed -i 's/^ServerSignature/#ServerSignature/g' /etc/apache2/conf-enabled/security.conf; \
    sed -i 's/^ServerTokens/#ServerTokens/g' /etc/apache2/conf-enabled/security.conf; \
    echo "ServerSignature Off" >> /etc/apache2/conf-enabled/security.conf; \
    echo "ServerTokens Prod" >> /etc/apache2/conf-enabled/security.conf; \
    a2enmod ssl; \
    a2enmod headers; \
    echo "SSLProtocol ALL -SSLv2 -SSLv3" >> /etc/apache2/apache2.conf

RUN docker-php-ext-install pdo pdo_mysql

# Add Soapclient
RUN apt-get update -y \
    && apt-get install -y libxml2-dev php-soap \
    && apt-get clean -y \
    && docker-php-ext-install soap

CMD npm install --silent

ADD ./docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf
ADD ./docker/001-default-ssl.conf /etc/apache2/sites-enabled/001-default-ssl.conf

RUN a2enmod rewrite

WORKDIR /var/www/html

EXPOSE 80

EXPOSE 443

ADD entrypoint.sh /opt/entrypoint.sh
RUN chmod a+x /opt/entrypoint.sh

ENTRYPOINT ["/opt/entrypoint.sh"]
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]