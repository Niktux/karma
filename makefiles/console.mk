#------------------------------------------------------------------------------
# Console
#------------------------------------------------------------------------------

CONSOLE_IMAGE_NAME=karma/console
CONTAINER_SOURCE_PATH=/usr/src/karma

console = docker run -it --rm --name karma_console \
	                 -v ${HOST_SOURCE_PATH}:${CONTAINER_SOURCE_PATH} \
	                 -w ${CONTAINER_SOURCE_PATH} \
	                 -u ${USER_ID}:${GROUP_ID} \
	                 ${CONSOLE_IMAGE_NAME} \
	                 php karma $1 $(CLI_ARGS)

# Spread cli arguments
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),phpunit))
    CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    $(eval $(CLI_ARGS):;@:)
endif

#------------------------------------------------------------------------------

console: create-console-image ## Run karma
	$(call console, )

create-console-image: docker/images/console/Dockerfile
	docker build -q -t ${CONSOLE_IMAGE_NAME} docker/images/console/

#------------------------------------------------------------------------------

clean-console:
	-docker rmi ${CONSOLE_IMAGE_NAME}

#------------------------------------------------------------------------------

.PHONY: console create-console-image clean-console

#------------------------------------------------------------------------------
