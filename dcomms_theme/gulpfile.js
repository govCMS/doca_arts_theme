'use strict';

// Include Gulp & Tools We'll Use
var gulp        = require('gulp'),
    $           = require('gulp-load-plugins')(),
    del         = require('del'),
    runSequence = require('run-sequence'),
    exec        = require('child_process').exec,
    path        = require('path'),
    pixrem      = require('gulp-pixrem'),
    rubySass    = require('gulp-ruby-sass'),
    sourcemaps  = require('gulp-sourcemaps'),

    // Task configuration.
    theme         = __dirname + '/',
    styleguide    = __dirname + '/styleguide/',

    // Get theme sub-directories from Compass' config.rb.
    compass          = require('compass-options').dirs({'config': theme + 'config.rb'}),

    uglify           = require('gulp-uglify'),
    mainBowerFiles   = require('main-bower-files'),
    modernizr        = require('gulp-modernizr'),
    concat           = require('gulp-concat');

// Build styleguide.
gulp.task('styleguide', ['clean:styleguide'], $.shell.task([
        // kss-node [source folder of files to parse] [destination folder] --template [location of template files]
        'kss-node <%= source %> <%= destination %> --template <%= template %>'
    ], {
        templateData: {
            source:       theme + compass.sass,
            destination:  styleguide,
            template:     styleguide + 'template'
        }
    }
));

// Lint JavaScript.
gulp.task('lint:js', function () {
    return gulp.src(theme + '/**/*.js')
        .pipe($.jshint())
        .pipe($.jshint.reporter('jshint-stylish'));
});

// Lint Sass.
gulp.task('lint:sass', function() {
    return gulp.src(theme + compass.sass + '/**/*.scss')
        .pipe($.scssLint({'bundleExec': true}));
});

// Lint Sass and JavaScript.
gulp.task('lint', function (cb) {
    runSequence(['lint:js', 'lint:sass'], cb);
});

// Concat and minify JavaScript.
gulp.task('scripts', function() {
  return gulp.src([theme + '/sass/components/**/*.js'])
    .pipe(concat('components.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest(theme + '/js'));
});

gulp.task('bower-scripts', function() {
  return gulp.src(mainBowerFiles({
    includeDev: true,
    paths: {
      bowerDirectory: __dirname + '/bower_components',
      bowerJson: __dirname + '/bower.json'
    },
    filter: '**/*.js'
  }))
    .pipe(concat('bower-components.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest(theme + 'js'));
});

gulp.task('bower-css', function() {
  return gulp.src(mainBowerFiles({
    includeDev: true,
    paths: {
      bowerDirectory: __dirname + '/bower_components',
      bowerJson: __dirname + '/bower.json'
    },
    filter: '**/*.css'
  }))
    .pipe(concat('bower-components.css'))
    .pipe(gulp.dest(theme + 'css'));
});

gulp.task('modernizr', function() {
  gulp.src(theme + './sass/components/**/*.{js,scss}')
    .pipe(modernizr("modernizr.min.js", {
      "options" : [
        "setClasses"
      ]
    }))
    .pipe(uglify())
    .pipe(gulp.dest("js/"))
});

process.chdir(theme);
gulp.task('sass:development', function() {
    return rubySass(theme + 'sass/', {
        compass: true,
        bundleExec: true,
        sourcemap: true,
        style: 'expanded'
    })
      .on('error', function (err) {
          console.error('Error!', err.message);
      })
      .pipe(pixrem())
      .pipe(sourcemaps.write())
      .pipe(gulp.dest('css'));
});

gulp.task('sass:production', function() {
    return rubySass(theme + 'sass/', {
        compass: true,
        bundleExec: true,
        style: 'compressed'
    })
      .on('error', function (err) {
          console.error('Error!', err.message);
      })
      .pipe(pixrem())
      .pipe(gulp.dest('css'));
});

// Styles
gulp.task('watch', ['modernizr', 'sass:development', 'lint', 'styleguide'],  function () {
  gulp.watch(theme + 'sass/**/*.scss', ['modernizr', 'sass:development', 'lint']);
  gulp.watch(theme + 'sass/**/*.html', ['styleguide']);
  gulp.watch(theme + 'sass/**/*.js', ['scripts']);
});

// Clean styleguide directory.
gulp.task('clean:styleguide', del.bind(null, [styleguide + '*.html', styleguide + 'public'], {force: true}));

// Clean CSS directory.
gulp.task('clean:css', del.bind(null, [theme + '**/.sass-cache', theme + compass.css + '/**/*.map'], {force: true}));

// Clean all directories.
gulp.task('clean', ['clean:css', 'clean:styleguide']);

// Production build of front-end.
gulp.task('build', ['clean', 'sass:production', 'styleguide', 'scripts', 'bower-css', 'bower-scripts', 'modernizr'], function (cb) {
    // Run linting last, otherwise its output gets lost.
    runSequence(['lint'], cb);
});

// The default task.
gulp.task('default', ['build']);


// Resources used to create this gulpfile.js:
// - https://github.com/google/web-starter-kit/blob/master/gulpfile.js
// - https://github.com/north/generator-north/blob/master/app/templates/Gulpfile.js
