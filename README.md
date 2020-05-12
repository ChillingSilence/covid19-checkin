COVID-19 Check-in for contact-tracing with Digi-ID
===========================
This project allows you to easily contact-trace as a local business with customers, requiring one "field" worth of information (ideally a mobile number) and a uniquely generated pseudo-anonymous Digi-ID (that is not re-used between sites).

Where businesses / places require the public to "check in" for the purpose of contact tracing, this allows that to occur without handing over any inherently personally identifiable information.

It can easily be modified to expand upon the base layer for the purposes of a hosting provider serving multiple businesses / sites.

Installation
============
* Create a MySQL database, import struct.sql into it ( mysql -u digibyte -p digikey < ./struct.sql )
* Configure database information and server url in config.php
* After the first user has signed up with their Digi-ID, navigate to /admin.php on that same device and your Digi-ID will be elevated to becoming an Admin user, allowing you to approve / deny other permission requests

Notes
=====
* GMP PHP extension is required
