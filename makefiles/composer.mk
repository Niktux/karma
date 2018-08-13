#------------------------------------------------------------------------------
# Composer
#------------------------------------------------------------------------------

COMPOSER_VERSION?=latest

composer = docker run -t -i --rm \
                -v ${HOST_SOURCE_PATH}:/var/www/app \
                -v ~/.cache/composer:/tmp/composer \
                -e COMPOSER_CACHE_DIR=/tmp/composer \
                -w /var/www/app \
                -u ${USER_ID}:${GROUP_ID} \
                composer:${COMPOSER_VERSION} $1 $2

# Spread cli arguments
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),composer))
    CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    $(eval $(CLI_ARGS):;@:)
endif

# Add ignore platform reqs for composer install & update
COMPOSER_ARGS=
ifeq (composer, $(firstword $(MAKECMDGOALS)))
    ifneq (,$(filter install update require,$(CLI_ARGS)))
        COMPOSER_ARGS=--ignore-platform-reqs
    endif
endif

#------------------------------------------------------------------------------

composer-init:
	mkdir -p ~/.cache/composer

composer: composer-init ## Run composer
	$(call composer, $(CLI_ARGS), $(COMPOSER_ARGS))

composer-install: composer-init
	$(call composer, install, --ignore-platform-reqs)

composer-install-no-dev: composer-init
	$(call composer, install, --no-dev --ignore-platform-reqs)

composer-update: composer-init
	$(call composer, update, --ignore-platform-reqs)

composer-dumpautoload: composer-init
	$(call composer, dumpautoload)

#------------------------------------------------------------------------------

clean-composer:
	-rm -rf vendor

#------------------------------------------------------------------------------

.PHONY: composer-init composer composer-install composer-update composer-dumpautoload clean-composer
