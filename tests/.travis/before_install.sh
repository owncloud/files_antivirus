#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
#

set -e

WORKDIR=$PWD
APP_NAME=$1
CORE_BRANCH=$2
DB=$3
echo "Work directory: $WORKDIR"
echo "Database: $DB"
cd ..
git clone --depth 1 -b $CORE_BRANCH https://github.com/owncloud/core
cd core
git submodule update --init
if [ -f Makefile ]; then
  make
fi

cd apps
cp -R ../../$APP_NAME/ .
cd $WORKDIR

if [ "$DB" == "mysqlmb4" ] ; then
  echo "Setting up mysqlmb4 ..."
  cat "$WORKDIR/../core/tests/docker/mysqlmb4/mb4.cnf" | sudo tee -a /etc/mysql/my.cnf
  sudo service mysql restart
  DB="mysql"
  cp $WORKDIR/../core/tests/docker/mysqlmb4.config.php ../core/config
fi

if [ "$DB" == "mysql" ] ; then
  echo "Setting up mysql ..."
  mysql -u root -e 'create database oc_autotest;'
  mysql -u root -e "CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY 'owncloud'";
  mysql -u root -e "grant all on oc_autotest.* to 'oc_autotest'@'localhost'";
  mysql -u root -e "SELECT User FROM mysql.user;"
fi

if [ "$DB" == "pgsql" ] ; then
  createuser -U travis -s oc_autotest
fi

if [ "$DB" == "oracle" ] ; then
  DOCKER_CONTAINER_ID=$(docker run -d deepdiver/docker-oracle-xe-11g)
  export DATABASEHOST=$(docker inspect --format="{{.NetworkSettings.IPAddress}}" "$DOCKER_CONTAINER_ID")

  # TODO: wait for oracle
  bash tests/.travis/before_install_oracle_module.sh
fi

#
# copy custom php.ini settings
#
#wget https://raw.githubusercontent.com/owncloud/administration/master/travis-ci/custom.ini
if [ $(phpenv version-name) != 'hhvm' ]; then
  phpenv config-add tests/.travis/custom.ini
fi

#
# copy install script
#
cd ../core
#if [ ! -f core_install.sh ]; then
#    wget https://raw.githubusercontent.com/owncloud/administration/master/travis-ci/core_install.sh
#fi

bash $WORKDIR/tests/.travis/core_install.sh $DB
