1. `git clone git@github.com:ypost/dummyjson-users.git` to clone the repository

2. `cd dummyjson-users` to get into project directory

3. `docker compose up -d` to run containers

4. `docker compose exec php bash` to log into the container

5. `composer install` to install dependencies

6. `composer check` to run all local tests (phpstan and phpunit)

7. `composer test:integration` to run the integration test using DummyJSON API

8. `exit` followed by `docker compose down` to shut down the container
