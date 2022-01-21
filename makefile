.PHONY: test

all:
	@echo "update-to-date"

empty:
	rm db/db.sqlite

user:
	sqlite3 db/db.sqlite < db/user.sql

migrate:
	vendor/bin/ruckus.php db:migrate

permissions:
	chmod 664 db/db.sqlite
	chown sobr:www-data db/db.sqlite

serve:
	vendor/bin/php-watcher public/index.php

debug:
	export XDEBUG_MODE=debug;vendor/bin/php-watcher public/index.php

test:
	vendor/bin/phpunit

coverage:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-text

coverage-report:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-html build

