#------------------------------------------------------------------------------
# Executables
#------------------------------------------------------------------------------

#------------------------------------------------------------------------------
# Docker executables
#------------------------------------------------------------------------------

# Environment automatically deployed are not interactive
DOCKER_TTY=
DOCKER_COMPOSE_INTERACTIVE=-T
ifeq ($(ENV_INTERACTIVE),true)
	DOCKER_TTY=-ti
	DOCKER_COMPOSE_INTERACTIVE=
endif

#------------------------------------------------------------------------------

DOCKER_RUN=docker run $(DOCKER_TTY)
DOCKER_EXEC=docker exec $(DOCKER_TTY)

#------------------------------------------------------------------------------

clean-docker:
	docker rmi onyx/frontend
