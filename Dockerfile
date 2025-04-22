# syntax=docker/dockerfile:1
FROM php:8.4.4-apache

# Enable mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Add some packages
RUN apt-get -y update
RUN apt-get -y install git
RUN apt-get -y install nano

# Add zip, for use downloading composer dependencies
RUN apt-get install -y libzip-dev zip
RUN docker-php-ext-install zip && docker-php-ext-enable zip

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Prompt
RUN echo "PS1='\u@\w# '" >> ~/.bashrc