FROM wordpress:php7.4-apache

# The official image already supplies cURL; avoid rebuilding it against obsolete APT packages.
RUN php -r "exit(extension_loaded('curl') ? 0 : 1);"
