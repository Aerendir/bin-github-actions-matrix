# Supported PHP Versions
PHP_VERSIONS := 8.1 8.2 8.3 8.4

# Versions
PHP_V ?= 8.3
SF_V ?= 7.2

# Helper variable to use in `docker-compose.yaml`
PHP_V_ID := $(shell echo $(PHP_V) | tr -d .)

# This value is the root folder of the library.
# It has to correspond to the name of the image in each Dockerfile:
#
#    FROM php:8.3-cli as folder-name-83
PROJECT_NAME := $(notdir $(shell pwd))

# Executables (local)
DOCKER_COMP = PROJECT_ROOT=`pwd` PHP_V=$(PHP_V) PHP_V_ID=$(PHP_V_ID) PROJECT_NAME=$(PROJECT_NAME) docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php
PHP_CONT_DEBUG = $(DOCKER_COMP) exec -e XDEBUG_MODE=debug -e XDEBUG_SESSION=1 php

# Executables
PHP_EX      = $(PHP_CONT) php
COMPOSER_EX = $(PHP_CONT) composer

# Misc
.DEFAULT_GOAL = help
.PHONY: help build start stax stop stop-v down sh composer initialize cov mut $(PHP_VERSIONS)

# Icons
ICON_THICK = \033[32m\xE2\x9C\x94\033[0m
ICON_CROSS = \033[31m\xE2\x9C\x96\033[0m

# If second argument is in the form `x.y` (ex. `8.2`), then use it to set `PHP_V`
ifneq ($(word 2,$(MAKECMDGOALS)),)
  ifeq ($(filter $(word 2,$(MAKECMDGOALS)),$(PHP_VERSIONS)),)
    $(error Unsupported PHP version "$(word 2,$(MAKECMDGOALS))". Supported versions are: $(PHP_VERSIONS))
  else
    PHP_V := $(word 2,$(MAKECMDGOALS))
    PHP_V_ID := $(shell echo $(PHP_V) | tr -d .)
    override MAKECMDGOALS := $(word 1,$(MAKECMDGOALS))
  endif
endif

##
##Help
##====
##

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/ '

cov: ## Opens the code coverage in the browser
	open var/coverage/coverage-html/index.html

mut: ## Opens the report of mutations in the browser
	open var/mutations.html

##
##Docker:

start: ## Starts the containers to run the lib (all in detached mode - no logs).
	$(MAKE) stop
	$(DOCKER_COMP) up -d

stax: ## Starts, WITH XDEBUG, the containers to run TrustBack.Me (all in detached mode - no logs).
	$(MAKE) stop
	XDEBUG_MODE=debug PROJECT_ROOT=`pwd` docker compose up -d

stafu: ## Starts the containers to run Coommercio (all in detached mode - no logs) and also syncs branches and dependencies (both PHP and JS).
	${MAKE} start
	git fetch
	gt sync
	gt s --stack --update-only
	${MAKE} composer c='install'

stop: ## Stops all containers for all PHP versions (using `docker compose stop`)
	for v in $(PHP_VERSIONS); do $(MAKE) stop-v PHP_V=$$v; done


down: ## Downs the docker hub (using `docker compose down`)
	$(DOCKER_COMP) down --remove-orphans -v

sh: ## Connects to the lib's main container
	$(PHP_CONT) bash

build: ## Builds the Docker images
	$(DOCKER_COMP) build --pull

initialize: ## Builds and start the containers
	$(MAKE) build PHP_V=$(PHP_V)
	$(MAKE) start PHP_V=$(PHP_V)

##
##Composer:

composer: ## Run Composer. Pass the parameter "c=" to run a given command, example: make composer c='install'
	$(eval c ?=install)
	$(COMPOSER_EX) $(c)

# Private commands
stop-v:
	@$(DOCKER_COMP) stop

# Avoids error `make: *** No rule to make target `8.4'.  Stop.`
$(PHP_VERSIONS):
	@true
