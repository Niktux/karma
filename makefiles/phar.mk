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

test-phar: prepare-env phar restore-env ## Compile and run phar
	docker run -it --rm --name karma_console \
               -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
               -w ${CONTAINER_SOURCE_PATH} \
               -u ${USER_ID}:${GROUP_ID} \
               ${CONSOLE_IMAGE_NAME} \
               php karma.phar hydrate $(CLI_ARGS)
