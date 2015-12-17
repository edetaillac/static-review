#!/bin/bash

# Install Pre-commit script for linux (debian/ubuntu) - Usage of apt-get

# =============================
# INSTALL composer
# =============================

if ! type composer > /dev/null; then
echo "Composer Global install..."
sudo bash <<EOF
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar /usr/local/bin/composer
EOF
fi

# =============================
# INSTALL xmllint
# =============================

if ! type xmllint > /dev/null; then
echo "LibXml install..."
sudo bash <<EOF
	apt-get install libxml2
EOF
fi

# =============================
# INSTALL nodejs & npm & Eslint
# =============================

# see https://github.com/nodejs/node-v0.x-archive/wiki/Installing-Node.js-via-package-manager

# =============================
# INSTALL scss_lint
# =============================

if ! type gem > /dev/null; then
echo "rubygems install..."
sudo bash <<EOF
	apt-get install rubygems
EOF
fi

if ! type sass-convert > /dev/null; then
echo "sass install..."
sudo bash <<EOF
	gem install sass
EOF
fi

if ! type scss-lint > /dev/null; then
echo "scss-lint install..."
sudo bash <<EOF
	gem install scss_lint
EOF
fi

# =============================
# Copying rules
# =============================

if [ -d src/Rules ]; then
    if [ ! -d $HOME/.precommitRules ]; then
        mkdir $HOME/.precommitRules
    fi
    echo "updating precommit linter rules..."
    rsync -avh src/Rules/ $HOME/.precommitRules/ > /dev/null
fi

# =============================
# Composer dependencies
# =============================

if ! type phpcpd > /dev/null; then
echo "phpcpd install..."
composer global require 'sebastian/phpcpd=*'
fi

if ! type php-cs-fixer > /dev/null; then
echo "php-cs-fixer install..."
composer global require 'fabpot/php-cs-fixer @stable'
fi

if ! type phpmd > /dev/null; then
echo "phpmd install..."
composer global require 'phpmd/phpmd=@stable'
fi

if ! type box > /dev/null; then
echo "box install..."
composer global require 'kherge/box=2.5.*'
fi

if ! type phpcs > /dev/null; then
echo "php_codesniffer install..."
composer global require 'squizlabs/php_codesniffer=2.*'
fi

if ! type jsonlint > /dev/null; then
echo "jsonlint install..."
composer global require 'seld/jsonlint=@stable'
fi

COMPOSERPATH=${HOME}/.composer/vendor/bin

if [ -d "$COMPOSERPATH" ] && [[ :$PATH: != *:"$COMPOSERPATH":* ]] ; then
        echo "Add export composer bin PATH on your .bashrc"
        echo "PATH=\$PATH:${COMPOSERPATH}"
        echo "And source it:"
        echo "source $HOME/.bashrc"
        echo "Re-execute ./install_precommit.sh"
        exit 0
fi

composer install --no-dev --optimize-autoloader

# =============================
# Build pre-commit
# =============================

box build

echo "Putting the precommit phar globally..."
echo "Moving precommit.phar to /usr/local/bin/precommit..."
sudo bash <<EOF
	mv precommit.phar /usr/local/bin/precommit
EOF
