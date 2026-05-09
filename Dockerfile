FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY . .

# Set correct permissions for uploads
RUN mkdir -p /var/www/html/logo /var/www/html/product_images \
    && chown -R www-data:www-data /var/www/html/logo /var/www/html/product_images \
    && chmod -R 755 /var/www/html/logo /var/www/html/product_images

# Apache config: allow .htaccess and set document root
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/rapidorders.conf \
    && a2enconf rapidorders

EXPOSE 80
