# Use the official PHP image as a base image
FROM php:8.2-apache

# Other Dockerfile configurations...

# Set the working directory
WORKDIR /var/www/html

# Copy the Laravel project files to the container
COPY . /var/www/html
# Copy other necessary files
COPY storage /var/www/html/storage/

# Install required dependencies
RUN apt-get update && \
    apt-get install -y libpng-dev && \
    rm -rf /var/lib/apt/lists/*

# Copy .env.example to .env if .env doesn't exist
RUN test -f .env || cp .env.example .env

# Install application dependencies
# RUN composer install
RUN a2enmod rewrite headers
RUN grep -q "APP_KEY=" .env || php artisan key:generate
RUN chmod -R 775 storage


# Install required extensions
# Copy your custom Apache configuration
RUN test -f /etc/apache2/sites-available/000-default.conf || cp apache-config.conf /etc/apache2/sites-available/000-default.conf

# Enable the custom Apache configuration
RUN a2ensite 000-default
RUN \
    php -m | grep -q 'pdo' || docker-php-ext-install pdo && \
    php -m | grep -q 'pdo_mysql' || docker-php-ext-install pdo_mysql && \
    php -m | grep -q 'gd' || docker-php-ext-install gd && \
    php -m | grep -q 'bcmath' || docker-php-ext-install bcmath && \
    php -m | grep -q 'exif' || docker-php-ext-install exif



# Set folder permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Specify the command to run on container start
CMD ["apache2-foreground"]
