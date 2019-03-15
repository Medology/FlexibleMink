FROM chekote/php:5.6.40-behat3.4


RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests \
    php5.6-xml && \
    apt-get remove -y && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*
