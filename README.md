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
 * a `playwright-tests` service that runs the end-to-end tests

The tests are now written with Playwright.
They cover the same critical flows:
 * WordPress install + plugin activation + baseline configuration
 * CAS login on WordPress
 * Gateway mode on/off
 * Single Logout on/off
 * Local login, create-user toggle, and URL settings persistence
 * URL bypass security behavior (disabled by default, configurable, and reset)

## Usage

To launch all the dockers, including the Playwright tests, with wordpress (on php8), simply run:
```
docker compose up --build --abort-on-container-exit
```

If you want to test with wordpress on php7, use instead :
```
docker compose -f docker-compose.yml -f docker-compose-php7.yml up --build --abort-on-container-exit
```

## Requirements

You have to install the last version of docker.

For debian for example, see the [official documentation](https://docs.docker.com/engine/install/debian/#install-using-the-repository)

## Ports and URLs

Ports 80 and 8080 are exposed on the host machine and must be free.

The WordPress instance is available at http://localhost but to be compliant with the Playwright tests, it is better to modify the /etc/hosts file to add the following line:
```
127.0.0.1 cas.example.org wordpress.example.org wordpress1.example.org wordpress2.example.org
```

With this, you can access the WordPress instance at http://wordpress.example.org and the CAS server at http://cas.example.org:8080/cas.

On the WordPress instance, the Playwright test configured Administrators are users with id joe on CAS/OpenLdap.
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

If you want to reset the WordPress instance, after launched docker-compose, you can simply remove the db container :
```
docker compose rm db
```

The recommended one-step Docker command is:
```
docker compose up --build playwright-suite
```

This runs Playwright in headed mode inside an Xvfb display, so it does not require an XServer on your host.
You can follow the execution step by step through the browser actions and the Playwright trace/report.
If you want to actually see the browser window on your desktop, you would need an X11/VNC setup.

If you only want to run the tests headless, use:
```
docker compose up --build --abort-on-container-exit
```

The HTML report and test results are mounted on the host under:
* `docker/playwright-tests/playwright-report`
* `docker/playwright-tests/test-results`

After a run, you can open the HTML report directly at `docker/playwright-tests/playwright-report/index.html`,
or serve the report only through Docker with:
```
docker compose up --build playwright-report
```

This starts the report viewer on port `9323`.


If you want to run the tests directly from the container image with a specific command, for example:
```
npm run test:e2e -- wp-cassify.spec.js
```

## Continuous integration tests with GitHub Actions, docker compose and Playwright

The project is also configured to run the tests on GitHub Actions.
The workflow is defined in the files `.github/workflows/docker-playwright-tests.yml` and `.github/workflows/docker-playwright-tests-php7.yml`.
The PHP7 workflow keeps compatibility with WordPress on php7 while sharing the same Playwright test suite.

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
