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
	export XDEBUG_MODE=debug;php public/index.php

test:
	vendor/bin/phpunit

coverage:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-text

coverage-report:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-html build

upload:
	curl -v -XPOST http://localhost:8000/upload/@sb:syntest.lan?access_token=ugw8243igya57aaABGFfgeyu --data-binary @$(FILE) -H "Content-Type: application/octet-stream"