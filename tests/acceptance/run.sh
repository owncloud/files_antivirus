#!/usr/bin/env bash

mkdir -p output

OC_PATH=../../../../
CORE_ACCEPTANCE_TESTS_PATH=tests/acceptance/
OCC=${OC_PATH}occ
BEHAT=${OC_PATH}lib/composer/behat/behat/bin/behat

SCENARIO_TO_RUN=$1
HIDE_OC_LOGS=$2

# avoid port collision on jenkins - use $EXECUTOR_NUMBER
if [ -z "${EXECUTOR_NUMBER}" ]; then
    EXECUTOR_NUMBER=0
fi
PORT=$((8080 + ${EXECUTOR_NUMBER}))
echo ${PORT}
php -S localhost:${PORT} -t ../../../../ &
PHPPID=$!
echo ${PHPPID}

export TEST_SERVER_URL="http://localhost:${PORT}"

#Set up personalized skeleton
PREVIOUS_SKELETON_DIR=$(${OCC} --no-warnings config:system:get skeletondirectory)
${OCC} config:system:set skeletondirectory --value="$(pwd)/$OC_PATH""$CORE_ACCEPTANCE_TESTS_PATH""skeleton"

#We cannot set password with csrf enabled
$OCC config:system:set csrf.disabled --value="true"

#Enable needed appS
PREVIOUS_ANTIVIRUS_APP_STATUS=$($OCC --no-warnings app:list "^files_antivirus$")

if [[ "${PREVIOUS_ANTIVIRUS_APP_STATUS}" =~ ^Disabled: ]]
then
	${OCC} app:enable files_antivirus || { echo "Unable to enable antivirus app" >&2; exit 1; }
	ANTIVIRUS_ENABLED_BY_SCRIPT=true;
else
	ANTIVIRUS_ENABLED_BY_SCRIPT=false;
fi

PREVIOUS_TESTING_APP_STATUS=$(${OCC} --no-warnings app:list "^testing$")

if [[ "${PREVIOUS_TESTING_APP_STATUS}" =~ ^Disabled: ]]
then
	${OCC} app:enable testing || { echo "Unable to enable testing app" >&2; exit 1; }
	TESTING_ENABLED_BY_SCRIPT=true;
else
	TESTING_ENABLED_BY_SCRIPT=false;
fi

if [ -n "${BEHAT_FILTER_TAGS}" ]; then
	if [[ ${BEHAT_FILTER_TAGS} != *@skip* ]]; then
		BEHAT_FILTER_TAGS="${BEHAT_FILTER_TAGS}&&~@skip"
	fi
else
	BEHAT_FILTER_TAGS="~@skip"
fi

BEHAT_FILTER_TAGS="${BEHAT_FILTER_TAGS}&&@api"

BEHAT_PARAMS='{
    "gherkin": {
        "filters": {
            "tags": "'"${BEHAT_FILTER_TAGS}"'"
        }
    }
}'

BEHAT_PARAMS="${BEHAT_PARAMS}" ${BEHAT} --strict -f junit -f pretty ${SCENARIO_TO_RUN}
RESULT=$?

kill ${PHPPID}

# Put back state of the antivirus app
if [ "${ANTIVIRUS_ENABLED_BY_SCRIPT}" = true ]
then
	${OCC} app:disable files_antivirus
fi

# Put back state of the testing app
if [ "${TESTING_ENABLED_BY_SCRIPT}" = true ]
then
	${OCC} app:disable testing
fi

# Put back personalized skeleton
if [ "A${PREVIOUS_SKELETON_DIR}" = "A" ]
then
	${OCC} config:system:delete skeletondirectory
else
	${OCC} config:system:set skeletondirectory --value="${PREVIOUS_SKELETON_DIR}"
fi

if [ -z $HIDE_OC_LOGS ]; then
	tail "${OC_PATH}/data/owncloud.log"
fi

echo "runsh: Exit code: $RESULT"
exit $RESULT
