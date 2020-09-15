#------------------------------------------------------------------------------
# Whalephant
#------------------------------------------------------------------------------

whalephant = $(DOCKER_RUN) --rm --name whalephant \
                             -v ${HOST_SOURCE_PATH}:/var/www/app \
                             -w /var/www/app \
                             -u ${USER_ID}:${GROUP_ID} \
                             php:7.1-cli \
                             ./whalephant generate $1

#------------------------------------------------------------------------------

docker/images/phpunit/Dockerfile: whalephant
	$(call whalephant, docker/images/phpunit)

docker/images/console/Dockerfile: whalephant
	$(call whalephant, docker/images/console)

whalephant:
	$(eval LATEST_VERSION := $(shell curl -L -s -H 'Accept: application/json' https://github.com/niktux/whalephant/releases/latest | sed -e 's/.*"tag_name":"\([^"]*\)".*/\1/'))
	@echo "Latest version of Whalephant is ${LATEST_VERSION}"
	wget -O whalephant -q https://github.com/Niktux/whalephant/releases/download/${LATEST_VERSION}/whalephant.phar
	chmod 0755 whalephant

#------------------------------------------------------------------------------

clean-whalephant: clean-generated-dockerfiles
	-rm -f whalephant

clean-generated-dockerfiles:
	-rm -f docker/images/phpunit/Dockerfile
	-rm -f docker/images/phpunit/php.ini
	-rm -f docker/images/console/Dockerfile
	-rm -f docker/images/console/php.ini

#------------------------------------------------------------------------------

.PHONY: clean-whalephant clean-generated-dockerfiles
