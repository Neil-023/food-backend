# Use the official PHP image with Apache and Composer
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/food-backend

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Change Apache root to Laravel public folder
RUN sed -i 's|/var/www/html|/var/www/food-backend/public|g' /etc/apache2/sites-available/000-default.conf

# Ensure storage and cache directories are writable
RUN chown -R www-data:www-data /var/www/food-backend/storage /var/www/food-backend/bootstrap/cache

# Enable Apache rewrite module (needed for Laravel routes)
RUN a2enmod rewrite

# Expose the default HTTP port
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
