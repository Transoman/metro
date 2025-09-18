#!/bin/bash

if [ "${SITE_URL}" != "localhost" ]; then
		ENV_HOST="https://${SITE_URL}"
else
		ENV_HOST="https://${SITE_URL}:${DOCKER_SITE_PORT}"
fi

cd /var/www/html;

until mysqladmin ping -h"${WORDPRESS_DB_HOST}" -u"$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" --silent; do
      sleep 2
done

if ! wp core is-installed --allow-root; then
    wp --allow-root core config --skip-plugins --skip-themes --dbname="${WORDPRESS_DB_NAME}" --dbuser="${WORDPRESS_DB_USER}" --dbpass="${WORDPRESS_DB_PASSWORD}" --dbhost="${WORDPRESS_DB_HOST}" --dbprefix="${WORDPRESS_TABLE_PREFIX}"
		wp --allow-root config set DB_CHARSET '"utf8"' --raw
    wp --allow-root config set DB_COLLATE '"utf8_unicode_ci"' --raw
    wp --allow-root core install --skip-plugins --skip-themes --skip-packages --url="${ENV_HOST}" --title="${SITE_TITLE}" --admin_user="${SITE_USER}" --admin_password="${SITE_USER_PASSWPRD}" --admin_email="${SITE_USER_EMAIL}"
    wp --allow-root user meta update ${SITE_USER_EMAIL} has_to_be_activated "verified"
    wp --allow-root config shuffle-salts --skip-plugins --skip-themes
    wp --allow-root --skip-plugins theme activate $(wp theme list --allow-root --format=json | jq -r .[0].name)
		wp --allow-root rewrite structure '/blog/%postname%/'
    wp --allow-root plugin activate --all
    chown -R www-data:www-data *
fi

if [ "${SITE_ENV}" != "production" ]; then
    wp --allow-root option set blog_public 0
fi

SITEURL=$(wp --allow-root eval 'echo get_option("siteurl");')
if [ "${SITEURL}" != "" ] && [ "${SITEURL}" != "${ENV_HOST}" ]; then
  wp --allow-root --no-color --skip-themes search-replace ${SITEURL} ${ENV_HOST} --all-tables
  wp --allow-root cache flush
  wp --allow-root rewrite flush
fi

wp --allow-root --no-color --skip-themes search-replace http:// https:// --all-tables
wp --allow-root config set WP_DEBUG true --raw && wp --allow-root config set WP_DEBUG_DISPLAY false --raw && wp --allow-root config set WP_DEBUG_LOG true --raw

exec php-fpm -F
