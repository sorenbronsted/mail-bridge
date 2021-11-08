
up:
	docker-compose up -d

down:
	docker-compose down
	#docker-compose down --remove-orphans

logs:
	docker-compose logs -f

bash:
	docker-compose exec mail-bridge bash

image:
	docker-compose build

composer:
	docker-compose exec composer composer install --ignore-platform-reqs
