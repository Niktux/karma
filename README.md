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
[variables]
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

Commands
--------

* **hydrate** : injects configuration values and generate target files (main command)
* **rollback** : restore generated files to their previous content (if backuped when hydrated)
* **display** : display all values for given environment
* **diff** : display values differences between two environment

To come : 
* **check** : exec some sanity checks

All commands are these options : 
* *confDir* : directory where conf files are stored (default : conf/)
* *master* : first configuration file to parse (default : master.conf)

Hydrate command
---------------
Injects configuration values and generate target files (main command)
```
karma hydrate [--confDir="..."] [--master="..."] [--env="..."] [--suffix="..."] [--dry-run] [--backup] sourcePath
```

Specific options :
* *dry-run* : TODO
* *suffix* : TODO
* *env* : TODO
* *backup* : TODO


Rollback command
----------------
Restore generated files to their previous content (if backuped when hydrated)
```
karma rollback [--confDir="..."] [--master="..."] [--suffix="..."] [--dry-run] sourcePath
```

Specific options :
* *dry-run* : TODO
* *suffix* : TODO

Display command
---------------
Display all values for given environment

```
karma display [--confDir="..."] [--master="..."] [--env="..."] [--value="..."]
```

Specific options :
* *env* : environment values to display (default : dev)
* *value* : filter, display only values that match this filter (optional)

*value* supports wildcard character ```*``` 

Wilcard can be escaped like this ```**```

Note that escaped wildcard is interpreted before wildcard. Ambigous expressions like ```***``` is understood as ```star then wildcard```

Examples : 
```
karma display --env=dev
karma display --env=prod
karma display --env=prod --value=false
karma display --env=prod --value=*2
karma display --env=prod --value=*www*
```

Diff command
------------
Display values differences between two environment

```
karma diff [--confDir="..."] [--master="..."] env1 env2
```


Example : 
```
karma diff dev prod
```

will output this kind of display :

```
Diff between dev and prod
|---------------------------------------|
|              | dev       | prod       |
|---------------------------------------|
| print_errors | true      | false      |
| server       | NOT FOUND | sql21      |
| search       | localhost | search21   |
| nullInProd   | true      | NULL       |
| db.user      | root      | <external> |
|---------------------------------------|
```

Configuration files syntax
--------------------------
TODO
(includes, default fallback, managing different env, external values, ...)

While this documentation section is not written, please read this full example : 

master.conf
````
[includes]
db.conf
subdir/other.conf

[variables]
var1:
    dev = value1
    staging, preprod = value2
    prod = value3
    default = value4
    
var2:
    preprod, prod = valA
    default = valB
```

db.conf
````
[externals]
secured.conf

[variables]
db.user:
    prod = <external>
    default = root
    
db.password:
    prod = <external>
    default = 
```

secured.conf
````
[includes]
otherSecured.conf

[variables]
db.user:
    prod = sqluser

db.password:
    prod = ThisIsASecretData
```



