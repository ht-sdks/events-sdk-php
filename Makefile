bootstrap:
	.buildscript/bootstrap.sh

dependencies: vendor

vendor: composer.phar
	@php ./composer.phar install

composer.phar:
	@curl -sS https://getcomposer.org/installer | php

test:
	@vendor/bin/phpunit
	@php composer.phar validate

release:
	@printf "releasing ${VERSION}..."
	@printf '<?php\nglobal $$HIGHTOUCH_VERSION;\n$$HIGHTOUCH_VERSION = "%b";\n' ${VERSION} > ./lib/Version.php
	@git changelog -t ${VERSION}
	@git release ${VERSION}

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock

.PHONY: bootstrap test release clean
