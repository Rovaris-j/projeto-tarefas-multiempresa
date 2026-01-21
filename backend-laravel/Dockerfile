# 1. Imagem base com PHP 7.4 (compatível com Laravel 8)
FROM php:7.4-fpm

# 2. Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# 3. Instala extensões PHP necessárias para o Laravel
RUN docker-php-ext-install pdo pdo_mysql zip mbstring

# 4. Copia o Composer (gerenciador de dependências PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Define a pasta de trabalho
WORKDIR /var/www

# 6. Copia o projeto Laravel para o container
COPY . .

# 7. Instala as dependências do Laravel
RUN composer install --no-interaction --prefer-dist

# 8. Ajusta permissões
RUN chown -R www-data:www-data /var/www

# 9. Porta usada pelo PHP-FPM
EXPOSE 9000
