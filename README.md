Karma 
=====

PHP CLI tool to hydrate source code with environment dependent values 


QA
--

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/94083ab1-1613-46c1-b380-ec575926ae39/big.png)](https://insight.sensiolabs.com/projects/94083ab1-1613-46c1-b380-ec575926ae39)

Service | Result
--- | ---
**Jenkins** | [![Build Status](http://jenkins.deboo.fr/job/Karma/badge/icon)](http://jenkins.deboo.fr/job/Karma/)
**Travis CI** (PHP 5.4 + 5.5) | [![Build Status](https://travis-ci.org/Niktux/karma.png?branch=master)](https://travis-ci.org/Niktux/karma)
**Packagist** | [![Latest Stable Version](https://poser.pugx.org/niktux/karma/v/stable.png)](https://packagist.org/packages/niktux/karma)

Installation
------------
Use composer :
```json
{
    "require": {
		    "niktux/karma" : "dev-master"
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

Command ```karma hydrate --env=prod src/``` will generate **db.ini** (without -dist) :

```ini
[Database]
Host=mysql.domain.com
User=myUser
Pass=mySecretPass
```

while command ```karma hydrate --env=dev src/``` will generate **db.ini** like this :

```ini
[Database]
Host=127.0.0.1
User=myUser
Pass=awfulPassword
```

Options
-------

TODO
* hydrate options (dry-run, master, confDir, suffix, ...)
* others command : rollback, display, check
* conf file syntax (includes, default fallback, managing different env)
