Karma 
=====

PHP CLI tool to hydrate source code with environment dependent values 

**:warning: _PHP5 users and PHP 7.3- users, please use Karma 5.x_**


QA
--

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/94083ab1-1613-46c1-b380-ec575926ae39/big.png)](https://insight.sensiolabs.com/projects/94083ab1-1613-46c1-b380-ec575926ae39)

Service | Result
--- | ---
**Travis CI** (PHP 7.4) | [![Build Status](https://travis-ci.org/Niktux/karma.png?branch=master)](https://travis-ci.org/Niktux/karma)
**Scrutinizer** | [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Niktux/karma/badges/quality-score.png?s=595d09c72316b5e706c3f78fb00807bc6b1515f1)](https://scrutinizer-ci.com/g/Niktux/karma/)
**Packagist** | [![Latest Stable Version](https://poser.pugx.org/niktux/karma/v/stable.png)](https://packagist.org/packages/niktux/karma)

Installation
------------
Download latest phar (recommended) :
```
  wget https://github.com/Niktux/karma/releases/download/7.4.0/karma.phar
```

Or use composer (disapproved)

```json
{
    "require": {
        "niktux/karma" : "~7.4"
    }
}
```

Full Documentation
------------------
You can find it here : http://karma-php.com/

Versionning
-----------
Karme use semver. It supports PHP 5.6 until Karma 5.6 version.
Next version dropped 5.6 support but also 7.0 to 7.3 one : that's why we jumped from Karma 5.6 to ... Karma 7.4
