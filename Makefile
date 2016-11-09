BOX_VERSION=2.7.4

phar: box.phar
	php -d phar.readonly=off box.phar build

box.phar:
	wget -q https://github.com/box-project/box2/releases/download/2.7.4/box-${BOX_VERSION}.phar
	mv box-${BOX_VERSION}.phar box.phar
