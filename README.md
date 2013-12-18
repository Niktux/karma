Karma [![Build Status](http://jenkins.deboo.fr/job/Karma/badge/icon)](http://jenkins.deboo.fr/job/Karma/)
=====

PHP CLI tool to hydrate source code with environment dependent values

Installation
------------
Use composer :
```json
{
    "require": {
		    "niktux/karma" : "*"
    }
}
```


Basic Usage 
-----------
This script will generate source files from template source files (*-dist).
For example : db.ini from db.ini-dist

db.ini-dist :
```ini
[Database]
Host=<%db.host%>
User=myUser
Pass=<%db.pass%>
```
master.conf:
```
[Variables]
db.host:
  prod = mysql.domain.com
  default = 127.0.0.1
  
db.pass:
  prod = mySecretPass
  preprod, staging = otherPass
  dev = awfulPassword
```

Command ```karma hydrate --env=prod src/``` will generate *db.ini* (without -dist) :

```ini
[Database]
Host=mysql.domain.com
User=myUser
Pass=mySecretPass
```

while command ```karma hydrate --env=dev src/``` will generate *db.ini* like this :

```ini
[Database]
Host=127.0.0.1
User=myUser
Pass=awfulPassword
```

Options
-------

TODO
hydrate options (dry-run, master, confDir, suffix, ...)
others command : rollback, display, check
conf file syntax (includes, default fallback, managing different env)
