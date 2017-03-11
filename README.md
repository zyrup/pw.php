# pw.php
Simple cipher-script file in PHP. Encrypt and decrypt data within the pw.php file with a partially adapted one-time pad cipher-script with a key derivation function of the users chosen password. It makes it easier for transferring the encrypted data between computers. Made and tested on OS X El Capitan.

##Shell:

Help:
```
php pw.php
```
Export content within the pw.php file into an non-existing file:
```
php pw.php export choosename.choosetype
```
Import content from a file into the pw.php file if pw.php has no data saved:
```
php pw.php import choosename.choosetype
```
Show content inside the pw.php in the console:
```
php pw.php show choosename.choosetype
```
