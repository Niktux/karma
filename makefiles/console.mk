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

switch-to-hydrate-test-profile:
	-rm .karma
	-ln -s .karma-hydrate .karma

switch-to-generate-test-profile:
	-rm .karma
	-ln -s .karma-generate .karma

console: switch-to-hydrate-test-profile create-console-image ## Run karma
	$(call console, )

k-hydrate: switch-to-hydrate-test-profile create-console-image ## Run karma hydrate command
	$(call console, -vvv hydrate -e prod)

k-generate: switch-to-generate-test-profile create-console-image ## Run karma generate command
	$(call console, -vvv generate)

create-console-image: docker/images/console/Dockerfile
	docker build -q -t ${CONSOLE_IMAGE_NAME} docker/images/console/

#------------------------------------------------------------------------------

clean-console:
	-docker rmi ${CONSOLE_IMAGE_NAME}

#------------------------------------------------------------------------------

.PHONY: console create-console-image clean-console switch-to-hydrate-test-profile switch-to-generate-test-profile k-generate

#------------------------------------------------------------------------------
