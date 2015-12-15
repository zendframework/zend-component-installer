#!/bin/bash
# Unpack secrets
tar xvf .travis/secrets.tar -C .travis

# Setup SSH agent
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .travis/build-key.pem
ssh-add .travis/build-key.pem

# Setup git defaults
git config --global user.email "matthew+component@weierophinney.net"
git config --global user.name "Matthew Weier O'Phinney"

# Get box and build PHAR
curl -LSs https://box-project.github.io/box2/installer.php | php
php box.phar build -vv
mv component-installer.phar component-installer.phar.tmp

# Add SSH-based remote
git remote add deploy git@github.com:weierophinney/component-installer.git
git fetch deploy

# Checkout gh-pages and add PHAR file and version
git checkout -b gh-pages deploy/gh-pages
mv component-installer.phar.tmp component-installer.phar
sha1sum component-installer.phar > component-installer.phar.version
git add component-installer.phar component-installer.phar.version

# Commit and push
git commit -m 'Rebuilt phar'
git push deploy gh-pages:gh-pages
