IApacheBackend failure
============================
Simple test case to prove IApacheBackend failure. To reproduce do this:

1) Login as ownCloud admin.

2) Create user "dep_tester123".

3) Logout.

4) Install and enable this app.

5) Access ownCloud, e.g. http://xxxxxxxxxx/owncloud/ , it fails!

6) See error in ownCloud log.


The error goes away when "dep_tester123" does an initial login/logout:

1) Delete browser cookies.

2) Uninstall this app.

3) Login as "dep_tester123"

4) Logout.

5) Install and enable this app.

6) Access ownCloud, e.g. http://xxxxxxxxxx/owncloud/ , it is successful!

7) See that there is no more error in ownCloud log.
