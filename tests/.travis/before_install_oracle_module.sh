#!/bin/bash

# build php module
git clone https://github.com/DeepDiver1975/oracle_instant_client_for_ubuntu_64bit.git instantclient
cd instantclient
sudo bash -c 'printf "\n" | python system_setup.py'

sudo mkdir -p /usr/lib/oracle/11.2/client64/rdbms/
sudo ln -s /usr/include/oracle/11.2/client64/ /usr/lib/oracle/11.2/client64/rdbms/public

sudo apt-get install -qq --force-yes libaio1
if [ "$TRAVIS_PHP_VERSION" == "7" ] ; then
  printf "/usr/lib/oracle/11.2/client64\n" | pecl install oci8
else
  mkdir /tmp/oci8
  cd /tmp/oci8
  wget https://pecl.php.net/get/oci8-2.0.12.tgz
  tar -xzf oci8-2.0.12.tgz
  cd oci8-2.0.12
  phpize
  ORACLE_HOME=/usr/lib/oracle/11.2/client64
  ./configure
  make install

  echo "extension=oci8.so" >> /home/travis/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi
