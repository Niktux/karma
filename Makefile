phar: phar-composer.phar
	php -d phar.readonly=off phar-composer.phar build niktux/karma

phar-composer.phar:
	wget https://github.com/clue/phar-composer/releases/download/v1.0.0/phar-composer.phar
