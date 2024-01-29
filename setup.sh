#!/bin/bash

# Bring Up Docker Containers
echo "Bringing up Docker Containers..."
docker-compose build
docker-compose up -d

git update-index --assume-unchanged storage/*
git update-index --assume-unchanged .env
git update-index --assume-unchanged .htaccess
git update-index --assume-unchanged .env.example

# Run migrations within the container (if needed)
# docker-compose exec web php artisan migrate

# Done!
echo "Setup completed. You can now access your Laravel application at http://localhost:9800"
