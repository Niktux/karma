#------------------------------------------------------------------------------
# PHPUnit
#------------------------------------------------------------------------------

PHPUNIT_IMAGE_NAME=karma/phpunit
CONTAINER_SOURCE_PATH=/usr/src/karma

phpunit = docker run -it --rm --name phpunit \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 -u ${USER_ID}:${GROUP_ID} \
	                 ${PHPUNIT_IMAGE_NAME} \
	                 vendor/bin/phpunit $1 $(CLI_ARGS)

# Spread cli arguments
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),phpunit))
    CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    $(eval $(CLI_ARGS):;@:)
endif

#------------------------------------------------------------------------------

phpunit: vendor/bin/phpunit create-phpunit-image ## Run unit tests
	$(call phpunit, )

phpunit-coverage: vendor/bin/phpunit create-phpunit-image
	$(call phpunit, --coverage-html=coverage/)

vendor/bin/phpunit: composer-install

create-phpunit-image: docker/images/phpunit/Dockerfile
	docker build -q -t ${PHPUNIT_IMAGE_NAME} docker/images/phpunit/

#------------------------------------------------------------------------------

clean-phpunit:
	-rm -rf coverage/
	-docker rmi ${PHPUNIT_IMAGE_NAME}

#------------------------------------------------------------------------------

.PHONY: phpunit phpunit-coverage create-phpunit-image clean-phpunit
