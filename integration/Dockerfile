FROM php:8.2-cli

RUN apt-get update && apt-get install -y unzip curl && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /sdk
COPY composer.json composer.lock ./
COPY src/ ./src/
RUN composer install --no-interaction --prefer-dist --no-dev

COPY integration/composer.json ./integration/
WORKDIR /sdk/integration
RUN composer install --no-interaction --prefer-dist

COPY integration/ ./
CMD ["php", "test.php"]
