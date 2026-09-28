# syntax=docker/dockerfile:1
# Используем официальный образ PHP 8.3 с предустановленным Apache
FROM php:8.3-apache

# Устанавливаем системные зависимости для расширений и локали
RUN apt-get update && apt-get install -y --no-install-recommends \
    locales \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libmagickwand-dev \
    ghostscript \
    unzip nano \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Настраиваем и устанавливаем расширения PHP через официальные утилиты
# mbstring, xml, mysqli, curl уже встроены или ставятся автоматически, если нужны
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip pdo_mysql

# Установка imagick и xdebug через PECL (так как это стороннее расширение)
RUN pecl install imagick xdebug && docker-php-ext-enable imagick xdebug

# Настройка русской локали
RUN sed -i -e 's/# ru_RU.UTF-8 UTF-8/ru_RU.UTF-8 UTF-8/' /etc/locale.gen && locale-gen
ENV LANG=ru_RU.UTF-8
ENV LC_ALL=ru_RU.UTF-8

# Настройка конфигурации Apache
RUN rm /var/www/html/* || true && rm /etc/apache2/sites-enabled/* || true
COPY rip.conf /etc/apache2/sites-available/rip.conf
COPY php.ini $PHP_INI_DIR/conf.d/unlimited-php.ini
RUN ln -s /etc/apache2/sites-available/rip.conf /etc/apache2/sites-enabled/rip.conf \
    && a2enmod rewrite \
    && chown www-data:www-data /var/www/html

# Официальный и элегантный способ подтянуть Composer прямо из его докер-образа
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Переключаемся на www-data для безопасной сборки зависимостей
USER www-data

COPY --chown=www-data:www-data composer.json composer.lock ./
ENV COMPOSER_CACHE_DIR=/tmp/.cache/composer

# Ставим зависимости с использованием вашего кэш-маунта
RUN --mount=type=cache,target=/tmp/.cache/composer php /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader

# Копируем остальной код
COPY --chown=www-data:www-data . .

# Чистим конфиг и создаем нужные Yii2 папки (сразу с правильными правами)
RUN rm -f rip.conf php.ini \
    && mkdir -p web/assets web/upload runtime

# Возвращаемся на root, так как Apache на 80 порту должен стартовать от суперпользователя
USER root

EXPOSE 80

# В официальном образе дефолтная CMD уже настроена на запуск Apache, 
# но мы дублируем её для явного контроля
CMD ["apache2-foreground"]
