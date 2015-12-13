#!/bin/bash
tar xvf .travis/secrets.tar -C .travis
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .travis/build_key.pem
ssh-add .travis/build_key.pem
curl -LSs https://box-project.github.io/box2/installer.php | php
php box.phar build -vv
mv component-installer.phar component-installer.phar.tmp
git remote add origin git@github.com:weierophinney/component-installer.git
git fetch origin
git checkout -b gh-pages origin/gh-pages
mv component-installer.phar.tmp component-installer.phar
sha1sum component-installer.phar > component-installer.phar.version
git add component-installer.phar component-installer.phar.version
git commit -m 'Rebuilt phar'
git push origin gh-pages:gh-pages
