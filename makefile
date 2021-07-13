
create:
	rm db/db.sqlite
	sqlite3 db/db.sqlite < db/create.sql

serve:
	php -S 0.0.0.0:8001 -t public
