# Front-end Development Style Guide

This directory holds the build scripts needed to automatically create a style guide based on the Sass source in `sites/all/themes/custom/hyde`.

## Installation

The build scripts require `node-kss` to be installed in this styleguide directory. [node-kss](https://github.com/kss-node/kss-node) is a Node.js port of KSS. To install node-kss:

1. Install Node.js on your local system. With Homebrew on Mac OS X, you can install it with `brew install node`.
2. Install gulp globally with `npm install -g gulp`.
2. Use Node.js' package manager, npm, to install node-kss and its dependencies.
   * From the project root, run: `npm install`

## Building the style guide

Each time a component is modified in the Kaurna theme, the classes should be documented using KSS as described on the [Node KSS website](https://github.com/kss-node/kss-node).

1. From inside the project, run: `gulp styleguide`
2. Access the site from the /styleguide/ path, e.g. [http://example.com/styleguide](http://example.com/styleguide/)
