Development and release environment for WP-CASSIFY - A WordPress CAS plugin
============================

This project provides a development, release and testing environment for the WP-CASSIFY plugin

The WP-CASSIFY plugin is a WordPress plugin that allows you to integrate a WordPress site with a CAS server.

The plugin is available on the official WordPress plugin website at https://wordpress.org/plugins/wp-cassify

For development purpose, the plugin is also available on GitHub from this official repository at https://github.com/WP-Cassify/wp-cassify-develop

In this GitHub repository, the plugin itself is in the wp-cassify directory.

So if you want to develop the plugin, you can clone this repository and edit the plugin in the wp-cassify directory.

Next, you can send a pull request to this official repository.

You can test the plugin locally with Docker Compose before opening a pull request.

GitHub Actions runs the Playwright suites with Docker Compose on pushes to `main` and pull requests targeting `main`. The PHP test workflow runs on pushes and pull requests without branch filters. In the official repository, the release workflow also runs both sets of tests when a version tag is pushed.

## Docker compose

The docker-compose file sets up 
 * a WordPress instance with the local `wp-cassify/` directory mounted as a plugin
 * a MariaDB database
 * an Apereo CAS server
 * with an OpenLDAP server

The same docker-compose provides also 
 * a `playwright-suite` service that runs the end-to-end tests

The Playwright tests cover:
 * WordPress install + plugin activation + baseline configuration
 * CAS login on WordPress
 * Gateway mode on/off
 * Single Logout on/off
 * Local login, create-user toggle, and URL settings persistence
 * URL bypass security behavior (disabled by default, configurable, and reset)
 * Multisite bootstrap, network activation, and CAS login on sub-sites
 * Manual account marking and purge: eligibility, content/file preservation, interrupted
   writes, resume, deletion hooks, database locks and task result summaries
 * Explicit CAS account deletion from a list of WordPress usernames or IDs, with
   preview, duplicate/exclusion reporting and the same content reassignment workflow

## Usage

To start the stack and run the Playwright tests with WordPress on PHP 8, run:
```
docker compose up
```

To test with WordPress on PHP 7.4, use:
```
docker compose -f docker-compose.yml -f docker-compose-php7.yml up
```

To run the multisite Playwright suite, use the dedicated override:
```
docker compose -f docker-compose.yml -f docker-compose-multisite.yml up
```

To run the multisite suite on PHP 7.4, combine the PHP 7 and multisite overrides:
```
docker compose -f docker-compose.yml -f docker-compose-php7.yml -f docker-compose-multisite.yml up
```

## Requirements

Install Docker Engine with the Docker Compose plugin (`docker compose`).

For debian for example, see the [official documentation](https://docs.docker.com/engine/install/debian/#install-using-the-repository)

## Ports and URLs

Ports 80 and 8080 are exposed on the host machine and must be free.

For browser access from the host, add these names to `/etc/hosts`. Playwright resolves them inside the Compose network.
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

If you want to reset the WordPress instance, after launched docker-compose and without stopping it, you can run the following command to reset the database:
```
docker compose exec db mariadb -ppass -e "DROP DATABASE wordpress; CREATE DATABASE wordpress;"
```

If your docker is stopped, juste remove the db container :
```
docker compose rm -s -f db
```

The default suite uses the named `wordpress-test-data` volume shared between
WordPress and Playwright. The multisite override uses the `./wordpress` bind mount
for both containers so the suite can edit `wp-config.php` and `.htaccess`.
Run the two suites in separate Compose projects when you need to preserve each
other's database and filesystem state. Resetting a database or removing a volume
destroys that test environment; do not use these commands against production.

## PHP tests

Run all PHP tests from the repository root:

```bash
php php-tests/run.php
```

The suite requires PHP with DOM, cURL and OpenSSL. It runs without WordPress,
a database or a CAS server. Warnings and deprecations fail tests, and the command
returns exit code 1 if any case fails.

Tests live outside the distributed `wp-cassify/` plugin directory. Key files include:

* `php-tests/run.php`: runs all suites and reports results.
* `php-tests/bootstrap.php`: loads plugin classes and supplies WordPress doubles and shared helpers.
* `php-tests/rule-solver.php`: rule parsing and evaluation.
* `php-tests/expiration.php`: ISO and legacy expiration dates.
* `php-tests/service-url.php`: callback URLs and query parameters.
* `php-tests/cas-response.php`: valid, empty and malformed CAS responses.

Each suite returns named test callbacks. Add new cases to the relevant suite;
register new suites in `php-tests/run.php`.
The `.github/workflows/php-tests.yml` workflow runs the same command on
PHP 7.0, PHP 7.4 and PHP 8.0 through 8.4. Playwright covers full WordPress/CAS workflows.

## Playwright tests

The recommended one-step Docker command is:
```
docker compose up --build playwright-suite
```

This runs Playwright in headed mode inside an Xvfb display, so it does not require an XServer on your host.

The HTML report is mounted on the host under:
* `docker/playwright-tests/playwright-report`

After a run, you can open the HTML report directly at `docker/playwright-tests/playwright-report/index.html`.

To run only the cleanup scenarios after the environment has been initialized:
```bash
docker compose run --rm playwright-suite npx playwright test wp-cassify.spec.js --grep 'Manual CAS account tools'
```

Set `PLAYWRIGHT_CLEANUP_VOLUME=true` on the test container to include the optional
600-account exercise. The single-site and multisite suites share the same cleanup
scenarios; network-specific cases are skipped on single-site installations.
Fixtures install a token-protected test MU plugin in the shared test volume;
never copy that test environment or its MU plugins to production.

If you want to rerun the tests without relaunching the whole docker-compose, reset the wordpress instance (see above) and then rerun the Playwright tests.

## Continuous integration tests with GitHub Actions, docker compose and Playwright

The workflows are defined in the files `.github/workflows/docker-playwright-tests.yml`, `.github/workflows/docker-playwright-tests-php7.yml`, and `.github/workflows/docker-playwright-tests-multisite.yml`.
The PHP 7 workflow runs the single-site Playwright suite on PHP 7.4.

## Development environment

To start WordPress with Xdebug, run:
```
mkdir -p wordpress
docker compose -f docker-compose.yml -f docker-compose-xdebug.yml up --build wordpress cas
```
Compose mounts `./wordpress` at `/var/www/html` and `./wp-cassify` at `/var/www/html/wp-content/plugins/wp-cassify`.
Open the repository in your IDE and configure it to listen for Xdebug on port 9003.

## Release on wordpress.org

The workflow in `.github/workflows/release.yml` runs when a tag in `X.Y.Z` format is pushed. It deploys `wp-cassify/` to WordPress.org SVN after the PHP and Playwright checks pass and the tag matches both the plugin version and the `Stable tag` in `wp-cassify/readme.txt`.

## License

This project is licensed under the GPLv2 License - same as WP-Cassify plugin and as WordPress itself.

### Upgrade tests

Run the isolated database and browser upgrade test with `bash docker/playwright-tests/run-upgrade.sh` for a single site or `bash docker/playwright-tests/run-upgrade.sh multisite` for a network.
It starts from the 2.5.0 tag and replaces the plugin files while keeping the test database and browser contexts.

## AI assistance

This project is developed with the assistance of AI agents.
