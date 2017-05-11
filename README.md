# DoCA Site Theme

The Department of Communications and Arts theme for the govCMS website

## Overview

This is a theme for Drupal 7 govCMS

## Development

### Structure

* doca_base - `common` and `admin` themes
* doca_theme - `custom` theme for the site

doca_base is managed by `git subtree` and belong to another project [https://github.com/govCMS/doca_base]. Please try to avoid editing the files directly in this folder and commit your updates to the doca_base project instead.

### Dependencies

* [Git](http://git-scm.com/)
* [Node](https://nodejs.org/en/), which includes the NPM package manager
* [bundler](http://bundler.io/), install all the required gems
* [Bower](https://bower.io/)

### Settings

* bower.json - to be removed soon
* config.rb - `Compass` configuration
* Gemfile - provides required Gems for `gulp` and `compass`
* gulpfile - gulp configuration
* package.json - install required node libraries for `gulp`

### Set up local environment

Run following commands under theme folder

```

bundle

```
