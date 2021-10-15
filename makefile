.PHONY: test

empty:
	rm db/db.sqlite

migrate:
	vendor/bin/ruckus.php db:migrate

permissions:
	chmod 664 db/db.sqlite
	chown sobr:www-data db/db.sqlite

serve:
	php -S 0.0.0.0:8001 -t public

debug:
	export XDEBUG_MODE=debug;php -S 0.0.0.0:8001 -t public

import:
	php src/cli/imap_import.php

test:
	vendor/bin/phpunit

coverage:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-text

coverage-report:
	export XDEBUG_MODE=coverage;vendor/bin/phpunit --coverage-html build

