let mix = require('laravel-mix')
require('laravel-mix-copy-watched');

let fs = require('fs-extra')

let getFiles = function (dir) {
  // get all 'files' in this directory
  // filter directories
  return fs.readdirSync(dir).filter(file => {
    if (!file.startsWith("_")) {
      return fs.statSync(`${dir}/${file}`).isFile();
    }
  });
};


/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

// Compile all the css per directory.
const directories = [
  'pages',
  'components',
  'components/commerce',
  'components/layout-builder',
  'components/product-teaser',
  'theme'
];


directories.forEach(directory => {
  getFiles('src/scss/' + directory).forEach(function (filepath) {
    mix.sass('src/scss/' + directory + '/' + filepath, 'dist/' + directory + '/')
  });
})

mix
  .js('src/js/main.js', 'dist/')
  .js('src/js/product.images.js', 'dist/')
  .js('src/js/throbber.js', 'dist/')

  .sass('src/scss/main.scss', 'dist/')
  .sass('src/scss/color.scss', 'dist/')

  .options({
    processCssUrls: false,
    postCss: [
      require('postcss-inline-svg')
    ],
    autoprefixer: {}
  });

// Directly copies the images, icons and fonts with no optimizations on the images
mix.copyWatched('src/images', 'dist/images');
mix.copyWatched('src/fonts/**/*', 'dist/fonts');
// Bootstrap
// mix.copyWatched('node_modules/bootstrap-icons/icons', 'src/icons');
mix.copyWatched('node_modules/bootstrap/dist/js/bootstrap.bundle.js', 'dist/bootstrap.js');
