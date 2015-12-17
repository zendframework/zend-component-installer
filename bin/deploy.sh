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
wget https://box-project.github.io/box2/manifest.json
BOX_URL=$(php bin/parse-manifest.php manifest.json)
rm manifest.json
wget -O box.phar ${BOX_URL}
chmod 755 box.phar
./box.phar build -vv
mv zend-component-installer.phar zend-component-installer.phar.tmp

# Add SSH-based remote
git remote add deploy git@github.com:weierophinney/component-installer.git
git fetch deploy

# Checkout gh-pages and add PHAR file and version
git checkout -b gh-pages deploy/gh-pages
mv zend-component-installer.phar.tmp zend-component-installer.phar
sha1sum zend-component-installer.phar > zend-component-installer.phar.version
git add zend-component-installer.phar zend-component-installer.phar.version

# Commit and push
git commit -m 'Rebuilt phar'
git push deploy gh-pages:gh-pages
