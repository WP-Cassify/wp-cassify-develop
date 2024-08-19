Development and release environment for WP-CASSIFY - A WordPress CAS plugin
============================

This project provides a development, release and testing environment for the WP-CASSIFY plugin

The WP-CASSIFY plugin is a WordPress plugin that allows you to integrate a WordPress site with a CAS server.

The plugin is available on the official WordPress plugin website at https://wordpress.org/plugins/wp-cassify

For development purpose, the plugin is also available on GitHub from this official repository at https://github.com/WP-Cassify/wp-cassify-develop

In this GitHub repository, the plugin itself is in the wp-cassify directory.

So if you want to develop the plugin, you can clone this repository and edit the plugin in the wp-cassify directory.

Next, you can send a pull request to this official repository.

Before to send a pull request, you can test the plugin with the docker-compose file provided in this repository.

Actually, at each pull request on this repository, the plugin is automatically tested with the docker-compose file provided in this repository.

Read on for more details.

## Docker compose

The docker-compose file sets up 
 * a WordPress instance with the WP-CASSIFY plugin installed
 * a MariaDB database
 * an Apereo CAS server
 * with an OpenLDAP server

The same docker-compose provides also 
 * a selenium chrome node
 * and a selenium runner that runs the tests

The tests are edited with Selenium IDE.
There are currently four tests:
 * one that make the install of wordpress and the plugin and a simple configuration of the plugin (wp-cassify)
 * one that make the login with CAS on wordpress
 * one that make the login with the gateway feature of the CAS protocol
 * one that make the logout  with the Single Logout feature of the CAS protocol

## Usage

To launch all the dockers, included the selenium tests, with wordpress (on php8), simply run:
```
docker compose up 
```

If you want to test with wordpress on php7, use instead :
```
docker compose -f docker-compose.yml -f docker-compose-php7.yml up 
```

## Requirements

You have to install the last version of docker.

For debian for example, see the [official documentation](https://docs.docker.com/engine/install/debian/#install-using-the-repository)

## Ports and URLs

Ports 80, 8080 and 4444 are exposed on the host machine and must be free.

The WordPress instance is available at http://localhost but to be compliant with selenium tests, it is better to modify the /etc/hosts file to add the following line:
```
127.0.0.1 cas.example.org wordpress.example.org wordpress1.example.org wordpress2.example.org
```

With this, you can access the WordPress instance at http://wordpress.example.org and the CAS server at http://cas.example.org:8080.

Selenium Grid is available at http://localhost:4444.

On the WordPress instance, the Selenium Test configured Administrators are users with id joe on CAS/OpenLdap.
So you can log in with the following credentials:
```
username: joe
password: pass
```

The local super-administrator of the WordPress instance that is created at the installation is:
```
username: adm
password: pass
```

## Reset the WordPress instance

If you want to reset the WordPress instance, after launched docker-compose, you can connect to the mariadb docker instance and remove/recreate the wordpress database.

To list the docker instances:
```  
docker compose ps
```

You take the container id of the mariadb instance and connect to it:
```
docker exec -it <container_id> bash
```

Then you connect to the mariadb database:
```
mariadb -ppass
```

You delete and (re)create the WordPress database:
```
DROP DATABASE wordpress;
CREATE DATABASE wordpress;
```

You can relaunch the selenium tests with Selenium IDE :
* docker/selenium-sides/01-wp-cassify-setup.side
* docker/selenium-sides/02-wp-cassify-cas-login.side
* docker/selenium-sides/03-test-gateway-on-and-off.side
* docker/selenium-sides/04-single-sign-on-off.side

... or you can also relaunch the tests via the docker 'selenium-runner' with the following command:
```
docker compose up selenium-runner 
```

... or you can also relaunch some tests via selenium-runner directly with the following command for example:
```
selenium-side-runner -d -s http://localhost:4444 docker/selenium-sides/03-test-gateway-on-and-off.side
```

## Continuous integration tests with GitHub Actions, docker compose and selenium

The project is also configured to run the tests on GitHub Actions. 
The workflow is defined in the file `.github/workflows/docker-selenium-tests.yml` and `.github/workflows/docker-selenium-tests-php7.yml` 
(to keep compatibility with WordPress on php7)

## Development environment

You can use Eclipse-Php or Visual Studio Code or PhpStorm to edit wp-cassify.

The best is to use the docker-compose file with xdebug to debug the plugin.
```
mkdir wordpress
docker compose -f docker-compose.yml -f docker-compose-xdebug.yml up
```
With this, you have ./wordpress that is a bind volume to /var/ww/html of the WordPress container.

To edit also the wp-cassify plugin inside the whole WordPress project, just make a symbolic link in ./wordpress/wp-content/plugins to the wp-cassify plugin directory.
```
rm -r ./wordpress/wp-content/plugins/wp-cassify
ln -s $(pwd)/wp-cassify $(pwd)/wordpress/wp-content/plugins/
```

Next you can open the project with your IDE from the directory ./wordpress.

For XDebug, you have to configure the IDE to listen on port 9003. 

## Release on wordpress.org

Thanks to https://github.com/marketplace/actions/wordpress-plugin-svn-deploy, we release the plugin on wordpress.org with a GitHub action .

To release the plugin, a GitHub action is triggered on each tag to synchronize the official subversion repository of the plugin with the GitHub repository. 

The action is defined in the file `.github/workflows/release.yml`.

## License

This project is licensed under the GPLv2 License - same as WP-Cassify plugin and as WordPress itself.
