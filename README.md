# DoCA Site Theme

The Department of Communications and Arts theme for the govCMS website

## Overview

This is a theme for Drupal 7 govCMS

## Development

### Structure

* doca_base - `common` and `admin` themes
* doca_theme - `custom` theme for the site

`doca_base` is managed by `git subtree` and belong to another project [https://github.com/govCMS/doca_base]. Please try to avoid editing the files directly in this folder and commit your updates to the doca_base project instead.

Update the `doca_base` to the latest version of repository, run this command in your theme

```
git subtree pull --prefix=doca_base --squash git@github.com:govCMS/doca_base.git 7.x-1.x
```

### Dependencies

* [Git](http://git-scm.com/)
* [Node](https://nodejs.org/en/), which includes the NPM package manager
* [bundler](http://bundler.io/), install all the required gems
* [Bower](https://bower.io/)

### Settings

* bower.json - vendors configuration
* config.rb - `Compass` configuration
* Gemfile - provides required Gems for `gulp` and `compass`
* gulpfile - gulp configuration
* package.json - install required node libraries for `gulp`

### Set up local environment

Run following commands to install global libraries

```
sudo gem update --system
gem install bundler
```

Run following commands in theme folder

```
bundle
npm install
```

#### For Mac developers

```
Installing sass 
Operation not permitted - /usr/bin/sass
```

If you have above issues, try

```
brew install ruby
```
