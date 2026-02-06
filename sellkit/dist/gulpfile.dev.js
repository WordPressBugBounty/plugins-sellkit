"use strict";

var _require = require('gulp'),
    src = _require.src,
    dest = _require.dest,
    series = _require.series,
    watch = _require.watch;

var zip = require('gulp-zip');

var del = require('del');

var run = require('gulp-run-command')["default"];

var sass = require('gulp-sass');

var autoprefixer = require('gulp-autoprefixer');

var uglify = require('gulp-uglify');

var stripDebug = require('gulp-strip-debug');

var rename = require('gulp-rename');

var bro = require('gulp-bro');

var babelify = require('babelify');

var gulpLoadPlugins = require('gulp-load-plugins');

var sassLint = require('gulp-sass-lint');
/**
 * Automatically load and store all Gulp plugins.
 */


var $ = gulpLoadPlugins({
  rename: {
    'gulp-clean-css': 'cleanCSS'
  }
});
var paths = {
  styles: {
    srcFull: 'assets/src/scss/**/*.scss',
    src: ['assets/src/scss/*.scss', 'assets/src/scss/admin/admin-feedback.scss', 'assets/src/scss/admin/admin.scss'],
    dest: 'assets/dist/css/'
  },
  scripts: {
    srcFull: 'assets/src/js/**/*.js',
    src: ['assets/src/js/widgets/elementor-init.js', 'assets/src/js/frontend/funnel-frontend.js', 'assets/src/js/frontend/funnel-settings-variables.js', 'assets/src/js/admin/admin-feedback.js', 'assets/src/js/editor/editor.js', 'assets/src/js/admin/admin.js'],
    dest: 'assets/dist/js/'
  }
};
/*
 * Lint Sass.
 */

function lintSass() {
  return src(paths.styles.srcFull).pipe(sassLint({
    options: {
      configFile: '.sass-lint.yml'
    }
  })).pipe(sassLint.format()).pipe(sassLint.failOnError());
}
/**
 * Task to clean.
 */


function clean() {
  return del(['release', '*.zip', 'assets/dist/css', 'assets/dist/js']);
}
/**
 * Create Zip.
 */


function releaseZip() {
  return src(['release/**']).pipe(zip('sellkit.zip')) // eslint-disable-next-line no-undef
  .pipe(dest(__dirname).on('end', function () {
    // Move files from release/sellkit to release/
    src('release/sellkit/**').pipe(dest('release').on('end', function () {
      return del('release/sellkit');
    }));
  }));
}

function release() {
  return src(['**', '!src/**', '!assets/src/**', '!includes/block-editor/blocks/*.{js,scss}', '!includes/block-editor/blocks/*/*.js', '!includes/block-editor/blocks/*/*/*/*.js', '!includes/block-editor/blocks/*/style/**', '!includes/block-editor/blocks/*/inner-blocks/**/*.js', '!includes/block-editor/blocks/*/fields/**', '!includes/block-editor/blocks/*/render/**', '!includes/block-editor/blocks/*/settings/**', '!README.md', '!cypress/**', '!build/**', '!node_modules/**', '!visual-diff/**', '!vendor/**', '!wpcs/**', '!*.{lock,json,xml,js,yml}']).pipe(dest('release/sellkit', {
    mode: '0755'
  }));
}
/*
 * Build sellkit styles.
 */


function buildStyles() {
  return src(paths.styles.src).pipe(sass({
    outputStyle: 'expanded'
  }).on('error', sass.logError)).pipe(autoprefixer({
    browsers: ['last 2 versions'],
    cascade: false
  })).pipe($.save('before-dest')).pipe(dest(paths.styles.dest)).pipe($.cleanCSS()).pipe($.rename({
    suffix: '.min'
  })).pipe(dest(paths.styles.dest)) // RTL
  .pipe($.save.restore('before-dest')).pipe($.rtlcss()).pipe($.rename({
    suffix: '-rtl'
  })).pipe(dest(paths.styles.dest)).pipe($.cleanCSS()).pipe($.rename({
    suffix: '.min'
  })).pipe(dest(paths.styles.dest));
}
/*
 * Build sellkit scripts.
 */


function buildScripts() {
  return src(paths.scripts.src, {
    sourcemaps: true
  }).pipe(bro({
    transform: [babelify.configure({
      presets: ['@babel/preset-env']
    })]
  })) // eslint-disable-next-line no-console
  .on('error', console.log).pipe(dest(paths.scripts.dest)) // .pipe( stripDebug() )
  .pipe(uglify()).pipe(rename({
    suffix: '.min'
  })).pipe(dest(paths.scripts.dest));
}
/*
 * Watch Sellkit files.
 */


module.exports.watch = function () {
  return watch(paths.styles.srcFull, series(buildStyles)), watch(paths.scripts.srcFull, series(buildScripts));
};

module.exports["default"] = series(run('npm run make:pot'), run('npm run build'), run('npm run lint:js'), lintSass, buildStyles, buildScripts);
module.exports.test = series(run('npm run lint:js'), lintSass);
module.exports.release = series(clean, run('npm run build'), // run( 'npm run lint:js' ),
lintSass, buildStyles, buildScripts, // run( 'npm run make:pot' ),
release, releaseZip);