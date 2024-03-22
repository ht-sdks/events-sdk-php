bootstrap:
	.buildscript/bootstrap.sh

dependencies: composer install

test:
	@vendor/bin/phpunit

release:
	@printf "releasing ${VERSION}..."
	@printf '<?php\nglobal $$HIGHTOUCH_VERSION;\n$$HIGHTOUCH_VERSION = "%b";\n' ${VERSION} > ./lib/Version.php
	@git changelog -t ${VERSION}
	@git release ${VERSION}

clean: rm -rf vendor composer.lock

.PHONY: bootstrap test release clean
