FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
    graphviz \
    git \
    zip \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /app
WORKDIR /app

RUN composer install
ENTRYPOINT ["php", "cli.php"]
