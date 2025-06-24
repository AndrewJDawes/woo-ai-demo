#!/usr/bin/env bash
set -x
until wp db check --skip-ssl --path=/var/www/html; do
    echo 'Waiting for database connection...' && sleep 5
done
if ! wp core is-installed --path=/var/www/html; then
    wp core install --url=${WORDPRESS_CONFIG_WP_HOME:-${WORDPRESS_PROTOCOL:-http}://${WORDPRESS_DOMAIN:-localhost}:${WORDPRESS_HOST_PORT:-8080}} \
        --title="${WORDPRESS_SITE_TITLE:-WordPress Site}" \
        --admin_user="${WORDPRESS_ADMIN_USER:-admin}" \
        --admin_password="${WORDPRESS_ADMIN_PASSWORD:-admin}" \
        --admin_email="${WORDPRESS_ADMIN_EMAIL:-admin@example.com}" \
        --path=/var/www/html \
        --skip-plugins \
        --skip-themes
fi
if [ -n "${WORDPRESS_PLUGINS_TO_ACTIVATE:-woocommerce wpsolr-free}" ]; then wp plugin activate ${WORDPRESS_PLUGINS_TO_ACTIVATE:-woocommerce wpsolr-free} --path=/var/www/html; fi
wp option update woocommerce_coming_soon no --path=/var/www/html
PRODUCTS_CSV_URL="${PRODUCTS_CSV_URL:-https://raw.githubusercontent.com/AndrewJDawes/woo-sample-data-set/refs/heads/main/products.csv}"
MAPPINGS_CSV_URL="${MAPPINGS_CSV_URL:-https://raw.githubusercontent.com/AndrewJDawes/woo-sample-data-set/refs/heads/main/mappings.csv}"
curl "$PRODUCTS_CSV_URL" -o /var/www/html/wp-content/uploads/products.csv &&
    curl "$MAPPINGS_CSV_URL" -o /var/www/html/wp-content/uploads/mappings.csv &&
    wp wc import-csv /var/www/html/wp-content/uploads/products.csv --mappings=/var/www/html/wp-content/uploads/mappings.csv --path=/var/www/html --user="${WORDPRESS_ADMIN_USER:-admin}"
wpsolrtemplatefile=$(mktemp)
WPSOLR_SETTINGS_URL="${WPSOLR_SETTINGS_URL:-https://raw.githubusercontent.com/AndrewJDawes/woo-ai-demo-wpsolr-settings/refs/heads/main/settings.json}"
curl "$WPSOLR_SETTINGS_URL" -o "$wpsolrtemplatefile"
wpsolrsettingsfile=$(mktemp)
eval 'echo "$(< "$wpsolrtemplatefile" )"' >"$wpsolrsettingsfile"
wp wpsolr import-settings-json "$wpsolrsettingsfile" --path=/var/www/html
wp wpsolr index-reindex
sleep infinity
