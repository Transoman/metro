ARG PHP_VERSION=${PHP_VERSION}
FROM php:${PHP_VERSION}-fpm
ARG WP_VERSION=${WP_VERSION}

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN set -ex; \
	apt-get update && apt-get install -y ghostscript libfreetype6-dev libicu-dev libjpeg-dev libpng-dev libwebp-dev libzip-dev curl libcurl4-openssl-dev vim mariadb-client nodejs npm git libonig-dev zlib1g-dev jq zip; \
	docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
	docker-php-ext-install -j "$(nproc)" bcmath exif gd intl mysqli curl zip mbstring pdo pdo_mysql;

RUN set -eux; \
	curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
	curl --silent --show-error -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli-nightly.phar && chmod +x /usr/local/bin/wp; \
	/usr/local/bin/wp package install git@github.com:trepmal/wp-revisions-cli.git --allow-root; \
	npm install -g yarn; \
	composer global require "squizlabs/php_codesniffer=*"

RUN set -eux; \
	mkdir -p /var/www/html && cd /var/www/html; \
	wp --allow-root --skip-plugins --skip-themes core download --version="${WP_VERSION}"; \
	chown -R www-data:www-data *;

CMD sed -i 's/\r$//' /check-and-init.sh

ENTRYPOINT sh /check-and-init.sh
