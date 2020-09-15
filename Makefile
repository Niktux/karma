###############################################################################
# ONYX Main Makefile
###############################################################################

HOST_SOURCE_PATH=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

export USER_ID
export GROUP_ID

#------------------------------------------------------------------------------

include makefiles/executables.mk
include makefiles/composer.mk
include makefiles/console.mk
include makefiles/phar.mk
include makefiles/phpunit.mk
include makefiles/whalephant.mk

#------------------------------------------------------------------------------

.DEFAULT_GOAL := help

help:
	@echo "========================================"
	@echo "Karma Makefile"
	@echo "========================================"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo "========================================"

#------------------------------------------------------------------------------

clean: clean-composer clean-phar clean-phpunit clean-whalephant ##Clean dev environment

#------------------------------------------------------------------------------

.PHONY: help clean
