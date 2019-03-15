FROM chekote/php:7.2.8-behat3.4


RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests \
    php7.2-xml && \
    apt-get remove -y && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*
