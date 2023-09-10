FROM node:18-alpine AS node
FROM webdevops/php-nginx-dev:8.2-alpine

#https://stackoverflow.com/questions/44447821/how-to-create-a-docker-image-for-php-and-node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN apk update && \
    apk add gettext && \
    npm install -g npm-check-updates && \
    npm install -g eslint && \
    npm install -g npm@9.8.1

#avoid permission error on github actions:
#Your cache folder contains root-owned files, due to a bug in
#npm ERR! previous versions of npm which has since been addressed.
RUN npm config set cache /app/tmp --global

# install pcov on alpine requires some addtional packages
RUN apk add gcc musl-dev make autoconf && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    echo 'pcov.directory = /app' >> /usr/local/etc/php/php.ini