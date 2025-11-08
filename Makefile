DOCKER_COMPOSE = docker compose -f docker/docker-compose.dev.yml

.PHONY: docker-build docker-up docker-stop docker-down install migrate jwt-keys console

docker-build:
	$(DOCKER_COMPOSE) build app

docker-up:
	$(DOCKER_COMPOSE) up -d

docker-stop:
	$(DOCKER_COMPOSE) stop

docker-down:
	$(DOCKER_COMPOSE) down --remove-orphans

install:
	$(DOCKER_COMPOSE) run --rm app composer install

migrate:
	$(DOCKER_COMPOSE) exec app php bin/console doctrine:migrations:migrate --no-interaction

jwt-keys:
	$(DOCKER_COMPOSE) exec app php bin/console lexik:jwt:generate-keypair --overwrite --skip-if-exists

console:
	$(DOCKER_COMPOSE) exec app php bin/console $(filter-out $@,$(MAKECMDGOALS))

%:
	@:

