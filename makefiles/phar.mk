BOX_VERSION=2.7.4

test-phar: prepare-env phar restore-env

prepare-env: composer-install-no-dev

restore-env: composer-install

phar: box.phar ## Package karma as phar
	php -d phar.readonly=off box.phar build

box.phar:
	wget -q https://github.com/box-project/box2/releases/download/2.7.4/box-${BOX_VERSION}.phar
	mv box-${BOX_VERSION}.phar box.phar

clean-phar:
	-rm box.phar
	-rm karma.phar
