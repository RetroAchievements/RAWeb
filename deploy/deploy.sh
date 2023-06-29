#!/usr/bin/env bash

# usage:
GIT_REMOTE="https://github.com/RetroAchievements/RAWeb"
GIT_BRANCH="master"

# php binary to use - should match the composer platform config and crontab php version
PHP_FPM='php8.0-fpm'
PHP_BIN='php'
NPM_BIN='npm'

GIT_DIR=vcs
CURRENT_DIR=current
RELEASES_DIR=releases
BASEDIR="$PWD"

# print all executed commands
set -x
# exit on error
set -e


### Prepare ###

# pull source from git
cd "${BASEDIR}"
rm -rf "${BASEDIR:?empty string}/${GIT_DIR}"
git clone "${GIT_REMOTE}" -b "${GIT_BRANCH}" --depth 1 "${BASEDIR}/${GIT_DIR}"

# install php dependencies
cd "${BASEDIR}/${GIT_DIR}"
composer install --no-interaction --no-dev --no-progress

# get version (git tag) for release directory name
cd "${BASEDIR}/${GIT_DIR}"
git fetch --tags
TAG="$(git describe --tags `git rev-list --tags --max-count=1`)"
COMMIT="$(git log -1 --pretty=format:'%h' --abbrev-commit)"
VERSION="${TAG}-${COMMIT}"
RELEASE_DIR="${RELEASES_DIR}/$(date +"%Y-%m-%dT%H%M%S")-${VERSION}"

# no git history and storage needed anymore
rm -rf "${BASEDIR:?empty string}/${GIT_DIR}/.git"
rm -rf "${BASEDIR:?empty string}/${GIT_DIR}/storage"

# move source to release directory
mkdir -p "${BASEDIR}/${RELEASES_DIR}"
mv "${BASEDIR}/${GIT_DIR}" "${BASEDIR}/${RELEASE_DIR}"

# link files and directories that carry over between releases
# use absolute paths as they have to work when release is linked to current
ln -snf "${BASEDIR}/storage" "${BASEDIR}/${RELEASE_DIR}/storage"
ln -snf "${BASEDIR}/.env" "${BASEDIR}/${RELEASE_DIR}/.env"


### Build ###

cd "${BASEDIR}/${RELEASE_DIR}"

${NPM_BIN} install
${NPM_BIN} run build
#${NPM_BIN} run apidoc

${PHP_BIN} artisan ra:storage:link --force
${PHP_BIN} artisan migrate --force


### Release ###

# activate release - note -n option to actually replace the symlink instead of placing the new link inside it
ln -snf "${BASEDIR}/${RELEASE_DIR}" "${BASEDIR}/${CURRENT_DIR}"
# update version in .env file
sed -i "s/APP_VERSION=.*/APP_VERSION=${VERSION}/g" .env
${PHP_BIN} artisan config:cache
#${PHP_BIN} artisan route:cache
#${PHP_BIN} artisan octane:reload
${PHP_BIN} artisan horizon:terminate

sudo -S service ${PHP_FPM} reload

### Cleanup ###

# cleanup releases

# keep the last five (pass +1 to tail)
#cd "${BASEDIR}/${RELEASES_DIR}"
#ls -1t | tail -n +6 | xargs -d '\n' rm -rf --

# keep the last
cd "${BASEDIR}/${RELEASES_DIR}"
ls -1t | tail -n +2 | xargs -d '\n' rm -rf --

# negative -n value not allowed on all systems
#ls -1tr | head -n -5 | xargs -d '\n' rm -rf --
