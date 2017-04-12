FROM php:7.0-fpm
MAINTAINER Matt Glaman <nmd.matt@gmail.com>
# Install modules
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
        libxml2-dev \
        supervisor \
    && docker-php-ext-install mcrypt pdo_mysql mysqli mbstring opcache soap bcmath \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

# Make memcache available
#RUN curl -L -o /root/memcache.tgz https://pecl.php.net/get/memcache-3.0.6.tgz && \
#	cd /root && \
#	tar -zxvf memcache.tgz && \
#	cd /root/memcache-3.0.6 && \
#	/usr/local/bin/phpize && \
#	./configure --with-php-config=/usr/local/bin/php-config && \
#	make  && \
#	make install && \
#	cd /root && \
#	rm -fr /root/memcache-3.0.6 && \
#	rm -fr /root/memcache.tgz

# Setup xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Setup redis
RUN pecl install redis
RUN docker-php-ext-enable redis

# XHPROF is not available in PHP 7.

RUN export VERSION=`php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;"` \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/${VERSION} \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp \
    && mv /tmp/blackfire-*.so `php -r "echo ini_get('extension_dir');"`/blackfire.so \
    && echo "extension=blackfire.so\nblackfire.agent_socket=\${BLACKFIRE_PORT}" > $PHP_INI_DIR/conf.d/blackfire.ini

# Set up unison
# See https://hub.docker.com/r/kalabox/unison/~/dockerfile/
ENV UNISON_VERSION=2.49.543
ENV UNISON_CODEROOT=/code
ENV UNISON_WEBROOT=/var/www
ENV UNISON_OPTIONS=

RUN apt-get update && apt-get install -y \
    curl \
  && curl -fsSL -o /tmp/Release.key "http://download.opensuse.org/repositories/home:ocaml/xUbuntu_14.04/Release.key" \
  && apt-key add - < /tmp/Release.key \
  && sh -c "echo 'deb http://download.opensuse.org/repositories/home:/ocaml/xUbuntu_14.04/ /' >> /etc/apt/sources.list.d/ocaml.list" \
  && apt-get update -y \
  && apt-get install -y --force-yes \
    ocaml \
    build-essential \
    exuberant-ctags \
  && curl -fsSL -o /tmp/unison.tar.gz "http://www.seas.upenn.edu/~bcpierce/unison//download/releases/unison-$UNISON_VERSION/unison-$UNISON_VERSION.tar.gz" && \
    cd /tmp && tar -xzvf unison.tar.gz && \
    rm -rf unison.tar.gz && \
    cd src \
  && make UISTYLE=text && \
    cp -f unison /usr/local/bin && \
    cp -f unison-fsmonitor /usr/local/bin \
  && apt-get purge -y ocaml build-essential exuberant-ctags \
  && apt-get clean autoclean \
  && apt-get autoremove -y \
  && rm -rf /var/lib/{apt,dpkg,cache,log}/ /tmp/* /var/tmp/*

# Set up supervisor
RUN mkdir -p /var/log/supervisor

CMD ["/usr/bin/supervisord"]
