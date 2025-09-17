# Profi.Dev WordPress Theme

This repository contains Profi.Dev WordPress theme starter kit.

## Requirements

Make sure all dependencies have been installed before moving on:

- [Docker Desktop](https://docs.docker.com/desktop/#download-and-install)
- lint, stylelint, docker, wsl extensions for VSCode installed.

Also it is nice to have some Linux image installed on your WSL2, like Ubuntu. Windows setup still possible but it will be slow due to slow access to the file system.

## Setup
During first run scripts would create new database with fresh wp setup.

Run:
1. `git config --global core.autocrlf false` 
2. `git config --global core.eol lf`

Then start preparing the repo:
1. Clone repo [profi-wp-base](git@git.profi.dev:profi.dev/wp-classic-theme-starter.git) to local machine.
2. Create a new repo at git.profi.dev (ex: projectdomain.tld).
3. Run following commands:
- `git remote remove origin`
- `git remote add origin git@git.profi.dev:profi.dev/new-repo-name.git - here newrepo.git is a newly created repo
- `git branch -M main`
- `git push -u origin main`
4. `cp .env.sample .env`
5. `composer install`
6. `yarn` or `npm i`
7. Check .env, correct some variables values if necessary.
8. Run `docker-compose up`

## Development process
1. Run `docker-compose up -d`
2. Run `yarn dev` (or `npm run dev`)
3. Access WP instance on https://localhost:4431/ for SSL (or other port set on .env as `DOCKER_SITE_PORT`)
4. Access PHPMyAdmin instance on https://localhost:4431/pma/
5. SMTP mail client available on https://localhost:4431/smtp/
6. Time to time run `git pull git@git.profi.dev:profi.dev/wp-classic-theme-starter.git main` to get most recent core files version.
7. WP debug logs available inside  `wp-content/debug.log` file

### using external libraries (if they are not compatible with es6 import, swiper 10 is compatible)
This will add library to dist bundle only once, instead of compiling it into each block:
1. Edit vite.config.js - uncomment `const { resolve } = require("path")` and block inside resolve -> aliias related to the swiepr.
2. Inside source code use newly created alias, e.g.`import Swiper from "swiper"` instead of `import Swiper from "swiper/core";`

## Deployment process
1. Check for unused uploads files by running `yarn check:uploads`. You can uncomment deletion to auto-clean.
2. Delete revisions: `yarn delete:revisions`
3. Prepare mysql dump and uploads folder archive by running the command: `yarn deploy:files`
4. Then, you can move files to your local disk (command example to move to root of disk D: under Windows) `mv uploads.zip dump.zip /mnt/d/`
5. Upload this files to a Google Drive. If this is not first version update files by `right click on it` -> `Manage versions` -> `Upload New Version` so URL would remain the same.
6. Provide files URLs along with the git repo URL to the deployer.

## License
License: commercial.
It is strictly forbidden unauthorized copy of this theme.

## 2Do
1. phpcs
2. [phpstan](https://github.com/phpstan/phpstan)
3. postcss-variable-compress
