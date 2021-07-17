
empty:
	rm db/db.sqlite
	sqlite3 db/db.sqlite < db/create.sql

pemissions:
	chmod 664 db/db.sqlite
	chgrp www-data db/db.sqlite

serve:
	php -S 0.0.0.0:8001 -t public

import:
	php src/imap_parse.php
