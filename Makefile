BOX_VERSION=2.7.4

test-phar: prepare-env phar restore-env

prepare-env:
	php composer.phar install --no-dev
	
restore-env:
	php composer.phar install

phar: box.phar
	php -d phar.readonly=off box.phar build

box.phar:
	wget -q https://github.com/box-project/box2/releases/download/2.7.4/box-${BOX_VERSION}.phar
	mv box-${BOX_VERSION}.phar box.phar
