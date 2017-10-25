# FlexibleMink
Mink Extensions for Commonly Used Assertions and Object Storage

## Dependancies
This project is built with Docker and uses Behat and PHPUnit for testing. You will need to install the following:
- [Docker](https://www.docker.com/)

The Behat configuration is set up to run against localhost. 

## Setup
FlexibleMink comes packaged with some basic tests which double as exampels and tests of the various contexts implemented. The tests are run on Google Chrome through Selenium against an Apache web server.

To setup the test environment, run the following commands in the project root directory.
```
> bin/containers up
> bin/init_project
```

This will spin up the Selenium and Apache docker containers and the install the node and composer dependencies into the project directory.

## Usage
Once the setup is complete, usage is quite simple. Run the following in the project root directory.
```
> bin/phpunit
> bin/behat
```

## Debugging
Selenium is configured for VNC on the default port (5900). If you have a VNC client, simply point it to ```vnc://localhost``` on port 5900. The password is the default password provided by Selenium, which is `secret` (as documented on [Selenium](https://github.com/SeleniumHQ/docker-selenium)).

.
