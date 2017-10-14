# mobile-cells-to-mysql

This script will convert mobile networks cell towers information data from a well-known CSV format to SQL script intented for loading into a MySQL (or compatible) database.

The schema of the database table expected to be available in order to use the generated SQL output can be found in [empty.sql](empty.sql).

You can freely download the CSV data from these places:

* Mozilla Location Service - https://location.services.mozilla.com/
* OpenCelliD               - https://opencellid.org/

## Note

Please make sure that the input file is downloaded from a trusted place, there are no checks for possible SQL injection attacks.

## Project Homepage

https://github.com/0xebef/mobile-cells-to-mysql

## License and Copyright

License: GPLv3 or later

Copyright (c) 2017, 0xebef
