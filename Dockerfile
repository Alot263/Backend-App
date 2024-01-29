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
    apt-get install -y libfreetype6-dev && \
    apt-get install -y unzip && \
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
RUN docker-php-ext-install gd

RUN \
    php -m | grep -q 'pdo' || docker-php-ext-install pdo && \
    php -m | grep -q 'pdo_mysql' || docker-php-ext-install pdo_mysql && \
    php -m | grep -q 'bcmath' || docker-php-ext-install bcmath && \
    php -m | grep -q 'zip' || docker-php-ext-install zip && \
    php -m | grep -q 'exif' || docker-php-ext-install exif

# Check for Composer installation and update to the latest version
RUN if ! command -v composer &> /dev/null; then \
    echo "Installing Composer globally..."; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    else \
    echo "Composer already installed."; \
    fi && \
    echo "Updating Composer..." && \
    composer self-update --2

# Check if vendor directory exists
COPY composer.json ./
RUN if [ ! -d "vendor" ]; then \
    # If vendor directory doesn't exist, run composer install
    echo "Running Composer install..." && \
    COMPOSER_MEMORY_LIMIT=-1 composer install; \
    else \
    # If vendor directory exists, run composer update
    echo "Running Composer update..." && \
    composer update --ignore-platform-req=ext-zip; \
    fi

# Set folder permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Specify the command to run on container start
CMD ["apache2-foreground"]
