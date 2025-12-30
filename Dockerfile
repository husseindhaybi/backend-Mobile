FROM php:8.1-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Enable mysqli
RUN docker-php-ext-enable mysqli

# Copy project files
COPY . /var/www/html/

# Give permission
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
