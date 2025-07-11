FROM php:8.1-apache AS base

ARG NODE_VERSION=22

# persistent dependencies
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    # Ghostscript is required for rendering PDF previews
    ghostscript \
    ; \
    rm -rf /var/lib/apt/lists/*

# install the PHP extensions we need (https://make.wordpress.org/hosting/handbook/handbook/server-environment/#php-extensions)
RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libavif-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg-dev \
    libmagickwand-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    ; \
    \
    docker-php-ext-configure gd \
    --with-avif \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    ; \
    docker-php-ext-install -j "$(nproc)" \
    bcmath \
    exif \
    gd \
    intl \
    mysqli \
    zip \
    ; \
    # https://pecl.php.net/package/imagick
    # https://github.com/Imagick/imagick/commit/5ae2ecf20a1157073bad0170106ad0cf74e01cb6 (causes a lot of build failures, but strangely only intermittent ones 🤔)
    # see also https://github.com/Imagick/imagick/pull/641
    # this is "pecl install imagick-3.7.0", but by hand so we can apply a small hack / part of the above commit
    curl -fL -o imagick.tgz 'https://pecl.php.net/get/imagick-3.7.0.tgz'; \
    echo '5a364354109029d224bcbb2e82e15b248be9b641227f45e63425c06531792d3e *imagick.tgz' | sha256sum -c -; \
    tar --extract --directory /tmp --file imagick.tgz imagick-3.7.0; \
    grep '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php; \
    test "$(grep -c '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php)" = '1'; \
    sed -i -e 's!^//#endif$!#endif!' /tmp/imagick-3.7.0/Imagick.stub.php; \
    grep '^//#endif$' /tmp/imagick-3.7.0/Imagick.stub.php && exit 1 || :; \
    docker-php-ext-install /tmp/imagick-3.7.0; \
    rm -rf imagick.tgz /tmp/imagick-3.7.0; \
    \
    # some misbehaving extensions end up outputting to stdout 🙈 (https://github.com/docker-library/wordpress/issues/669#issuecomment-993945967)
    out="$(php -r 'exit(0);')"; \
    [ -z "$out" ]; \
    err="$(php -r 'exit(0);' 3>&1 1>&2 2>&3)"; \
    [ -z "$err" ]; \
    \
    extDir="$(php -r 'echo ini_get("extension_dir");')"; \
    [ -d "$extDir" ]; \
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$extDir"/*.so \
    | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
    | sort -u \
    | xargs -r dpkg-query --search \
    | cut -d: -f1 \
    | sort -u \
    | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    \
    ! { ldd "$extDir"/*.so | grep 'not found'; }; \
    # check for output like "PHP Warning:  PHP Startup: Unable to load dynamic library 'foo' (tried: ...)
    err="$(php --version 3>&1 1>&2 2>&3)"; \
    [ -z "$err" ]

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN set -eux; \
    docker-php-ext-enable opcache; \
    { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini
# https://wordpress.org/support/article/editing-wp-config-php/#configure-error-logging
RUN { \
    # https://www.php.net/manual/en/errorfunc.constants.php
    # https://github.com/docker-library/wordpress/issues/420#issuecomment-517839670
    echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'log_errors_max_len = 1024'; \
    echo 'ignore_repeated_errors = On'; \
    echo 'ignore_repeated_source = Off'; \
    echo 'html_errors = Off'; \
    } > /usr/local/etc/php/conf.d/error-logging.ini

RUN set -eux; \
    a2enmod rewrite expires; \
    \
    # https://httpd.apache.org/docs/2.4/mod/mod_remoteip.html
    a2enmod remoteip; \
    { \
    echo 'RemoteIPHeader X-Forwarded-For'; \
    # these IP ranges are reserved for "private" use and should thus *usually* be safe inside Docker
    echo 'RemoteIPInternalProxy 10.0.0.0/8'; \
    echo 'RemoteIPInternalProxy 172.16.0.0/12'; \
    echo 'RemoteIPInternalProxy 192.168.0.0/16'; \
    echo 'RemoteIPInternalProxy 169.254.0.0/16'; \
    echo 'RemoteIPInternalProxy 127.0.0.0/8'; \
    } > /etc/apache2/conf-available/remoteip.conf; \
    a2enconf remoteip; \
    # https://github.com/docker-library/wordpress/issues/383#issuecomment-507886512
    # (replace all instances of "%h" with "%a" in LogFormat)
    find /etc/apache2 -type f -name '*.conf' -exec sed -ri 's/([[:space:]]*LogFormat[[:space:]]+"[^"]*)%h([^"]*")/\1%a\2/g' '{}' +

# Copy HTML files
COPY --chown=www-data:www-data ./html /var/www/html

USER www-data

RUN mkdir -p /var/www/html/wp-content/uploads

# DANGER - doing this causes data to be written to anonymous volumes
# VOLUME /var/www/html

FROM wordpress:cli AS cli

USER root

# Create a user named wpcli with UID 33 and GID 33
# Create a user named wpcli with UID 33 and GID 33 (Alpine style)
RUN set -eux; \
    addgroup -g 33 wpcli; \
    adduser -u 33 -G wpcli -h /home/wpcli -s /bin/sh -D "wpcli"; \
    mkdir -p /home/wpcli; \
    chown wpcli:wpcli /home/wpcli; \
    chmod 755 /home/wpcli;

USER wpcli

COPY --from=base --chown=wpcli:wpcli /var/www/html /var/www/html

FROM cli AS init

COPY ./scripts /scripts

USER root

RUN apk add --no-cache gomplate

USER wpcli

FROM init AS dev

ARG NODE_VERSION=22

ENV SHELL=/bin/bash

USER root

RUN apk add --no-cache sudo git

RUN mkdir -p /etc/sudoers.d; \
    echo 'wpcli ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/wpcli; \
    chmod 0440 /etc/sudoers.d/wpcli

# Download and install fnm:
RUN curl -fsSL https://fnm.vercel.app/install | bash -s -- --install-dir "$HOME/.fnm" \
    && cp "$HOME/.fnm/fnm" /usr/bin && fnm install $NODE_VERSION \
    && echo 'eval "$(fnm env --use-on-cd --shell bash)"' >> "$HOME/.bashrc"

USER wpcli
