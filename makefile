.PHONY: test

empty:
	rm db/db.sqlite
	sqlite3 db/db.sqlite < db/create.sql

permissions:
	chmod 664 db/db.sqlite
	chgrp www-data db/db.sqlite

serve:
	php -S 0.0.0.0:8001 -t public

import:
	php src/cli/imap_parse.php

test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-text

coverage-report:
	vendor/bin/phpunit --coverage-html build

