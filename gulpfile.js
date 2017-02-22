'use strict';

/**
 * @file
 * Provides gulp tasks to compile sass and to minify
 * css and js.
 */

var parameters = require('yargs').argv,
    gulp = require('gulp'),
    gulpConcat = require('gulp-concat'),
    gulpIf = require('gulp-if'),
    uglify = require('gulp-uglify'),
    sass = require('gulp-sass'),
    cssmin = require('gulp-cssmin');

/*
    This object is configurable and extend-able.
    Define css/js that you want to compile in module/theme.

    NOTE: Source js and scss are expected to be located in
    {root}/assets/{js|scss}.
 */
var sources = {
    dcc_theme : { // module name
        root : './docroot/themes/custom/dcc_theme/',
        scss : {
            'components/*' : 'components/*',
            'content/*' : 'content/*',
            'base/*' : 'base.css',
        }
    }
};

var extensionResult = {'scss' : 'css', 'js' : 'js'};

/*
    Parses the sources defined and sends them to a callback.
    This is used in the tasks as a utility. DRY and clean code.

    Callback receives: module, source, destination and optionally
    output if in destination a name is the last thing after '/'.
 */
function eachInSources (extension, callback) {
    for (var module in sources) {
        for (var from in sources[module][extension]) {
            // The user might have forgot the last slash.
            if (sources[module].root.slice(-1) != '/') {
                sources[module].root += '/';
            }

            var source = sources[module].root + 'assets/' + extension + '/' + from,
                destination = sources[module].root + extensionResult[extension] + '/' + sources[module][extension][from];

            // Append the extension for sources as they are not required.
            if (source.slice(-(extension.length + 1)) != ('.' + extension)) {
                source += '.' + extension;
            }

            // Determine whether there is an output name. If there is
            // we will concat all source files into one, else each
            // will be one by it's own in the output.
            var destinationLast = destination.slice(-1),
                output = null;
            if(destinationLast != '/' && destinationLast != '*') {
                destination = destination.split('/');
                output = destination.pop();
                destination = destination.join('/');
            } else if (destinationLast == '*') {
                destination = destination.slice(0, -1);
            }

            callback(module, source, destination, output);
        }
    }
}

/*
    Task to compile sass files.
 */
gulp.task('scss', function () {
    var sassOptions = {};
    if (parameters.compress) {
        sassOptions.outputStyle = 'compressed';
    }

    eachInSources('scss', function(module, source, destination, output) {
        gulp.src(source)
            .pipe(sass.sync(sassOptions).on('error', sass.logError))
            .pipe(gulpIf(output != null, gulpConcat(output || "~")))
            .pipe(gulpIf(parameters.compress, cssmin()))
            .pipe(gulp.dest(destination, {overwrite: true}));

        if (output) { console.log('Compiled scss: ' + output); }
        else { console.log('Compiled all scss in: ' + source); }
    });
});
// Adds alias for convenience.
gulp.task('sass', ['scss']);

/*
    Task to compile javascript files.
 */
gulp.task('js', function() {
    eachInSources('js', function(module, source, destination, output) {
        gulp.src(source)
            .pipe(gulpIf(output != null, gulpConcat(output || "~")))
            .pipe(gulpIf(parameters.compress, uglify()))
            .pipe(gulp.dest(destination, {overwrite: true}));

        if (output) { console.log('Compiled js: ' + output); }
        else { console.log('Compiled all js in: ' + source); }
    });
});

/*
    Task to trigger file watching. This will watch every supported file.
 */
gulp.task('watch', function () {
    for (var extension in extensionResult) {
        eachInSources(extension, function(module, source) {
            gulp.watch(source, [this.extension]);
            console.log('Watching ' + this.extension + ': ' + source);
        }.bind({extension: extension}));
    }
});

// Default task to compile all supported files.
gulp.task('default', Object.keys(extensionResult));
