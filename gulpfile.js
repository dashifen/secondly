const {src, dest, parallel, watch} = require('gulp'),
  webpackConfig = require('./webpack.config.js'),
  sourcemaps = require('gulp-sourcemaps'),
  autoPrefixer = require('autoprefixer'),
  browserSync = require('browser-sync'),
  webpack = require('webpack-stream'),
  postcss = require('gulp-postcss'),
  plumber = require('gulp-plumber'),
  named = require('vinyl-named'),
  through = require('through2'),
  cssNano = require('cssnano'),
  sass = require('gulp-sass'),
  env = require('./.env.js');

const paths = {
  css: {
    src: 'assets/styles/**/*.scss',
    entry: 'assets/styles/dashifen.scss',
    dest: 'assets/'
  },
  js: {
    src: [
      'assets/scripts/**/*.js',
      'assets/scripts/**/*.vue'
    ],
    entry: 'assets/scripts/dashifen.js',
    dest: 'assets/'
  },
  content: [
    '**/*.twig',
    '**/*.php',
    '*.php'
  ]
};

const css = async function () {
  return src(paths.css.entry)
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass())
    .pipe(postcss([autoPrefixer(), cssNano()]))
    .pipe(sourcemaps.write())
    .pipe(dest(paths.css.dest))
    .pipe(browserSync.stream());
};

const jsDev = async function () {
  return js('development');
};

const js = async function (webpackMode) {
  const localConfig = webpackConfig;
  localConfig.mode = webpackMode;

  src(paths.js.entry)
    .pipe(plumber())
    .pipe(named())
    .pipe(webpack(localConfig))
    .pipe(sourcemaps.init({loadMaps: true}))
    .pipe(through.obj(function (file, enc, cb) {
      const isSourceMap = /\.map$/.test(file.path);
      if (!isSourceMap) {
        this.push(file);
      }
      cb();
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(dest(paths.js.dest))
    .pipe(browserSync.stream());
};

const jsProd = async function () {
  return js('production');
};

const watcher = async function () {
  browserSync.init({
    proxy: "https://wordpress-dev.site",
    host: "wordpress-dev.site",
    open: "external",
    port: "8080",
    https: {
      key: env.key,
      cert: env.cert,
    }
  });

  css();
  jsDev();
  watch(paths.content).on('change', browserSync.reload);
  watch(paths.js.src, jsDev);
  watch(paths.css.src, css);
};

const justWatch = async function () {
  css();
  jsDev();
  watch(paths.js.src, jsDev);
  watch(paths.css.src, css);
}

exports.css = css;
exports.js = jsDev;
exports.jsProd = jsProd;
exports.watch = watcher;
exports.justWatch = justWatch;
exports.build = parallel(css, jsProd);
exports.default = parallel(css, jsDev);

