#!/usr/bin/env bash
git clone git://${GH_REPO}
git config user.email ${EMAIL}
git config user.name ${USER}
git checkout master
git remote add acquia ${ACQUIA_REPO}
git branch acquia_master --track acquia/master
git checkout acquia_master
git merge master
git push acquia HEAD:master