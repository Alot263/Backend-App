#!/bin/bash

# Check for Composer installation
if ! command -v composer &> /dev/null; then
  echo "Installing Composer globally..."
  # Install Composer globally
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
else
  echo "Composer already installed."
fi

composer clear-cache

# Update Composer to the latest version
echo "Updating Composer..."
composer self-update --2

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
  # If vendor directory doesn't exist, run composer update
  echo "Running Composer update..."
  composer update
else
  # If vendor directory exists, run composer install
  echo "Running Composer install..."
  COMPOSER_MEMORY_LIMIT=-1 composer install
fi

# Additional Setup Steps (if any)
# Add any other setup steps specific to your project here

# Bring Up Docker Containers
echo "Bringing up Docker Containers..."
docker-compose up -d

git update-index --assume-unchanged storage/*
git update-index --assume-unchanged .env
git update-index --assume-unchanged .htaccess
git update-index --assume-unchanged .env.example



# Run migrations within the container (if needed)
# docker-compose exec web php artisan migrate

# Done!
echo "Setup completed. You can now access your Laravel application at http://localhost:9800"
