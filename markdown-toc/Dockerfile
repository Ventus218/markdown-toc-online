FROM php:apache

WORKDIR /var/www/html

RUN apt update
RUN apt --assume-yes install npm
RUN npm install -g markdown-toc

COPY ./serve .
