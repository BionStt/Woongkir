/**
 * Import modules
 */
const gulp = require('gulp');
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');
const iife = require('gulp-iife');
const concat = require('gulp-concat');
const sass = require('gulp-sass');
const autoprefixer = require('gulp-autoprefixer');
const plumber = require('gulp-plumber');
const notify = require('gulp-notify');
const browserSync = require('browser-sync').create();
const argv = require('yargs').argv;
const gulpif = require('gulp-if');
const cleanCSS = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const wpPot = require('gulp-wp-pot');
const phpcs = require('gulp-phpcs');
const bump = require('gulp-bump');

/**
 * Local variables
 */
const prefix = 'woongkir';
const project = 'Woongkir';

const assets = [
    {
        type: 'scripts',
        target: 'backend',
        sources: [
            'shared.js',
            'backend.js',
        ],
        targetDir: 'assets/js/',
        sourcesDir: 'assets/src/js/',
        isPrefixed: true,
        isIife: true,
        isSourceMap: false,
    },
    {
        type: 'scripts',
        target: 'frontend',
        sources: [
            'shared.js',
            'frontend.js',
        ],
        targetDir: 'assets/js/',
        sourcesDir: 'assets/src/js/',
        isPrefixed: true,
        isIife: true,
        isSourceMap: false,
    },
    {
        type: 'scripts',
        target: 'lockr',
        sources: [
            'plugins/lockr.js',
        ],
        targetDir: 'assets/js/',
        sourcesDir: 'assets/src/js/',
        isPrefixed: false,
        isIife: false,
        isSourceMap: false,
    },
    {
        type: 'styles',
        target: 'backend',
        sources: [
            'backend.scss',
        ],
        targetDir: 'assets/css/',
        sourcesDir: 'assets/src/scss/',
        isPrefixed: true,
        isSourceMap: false,
    },
    {
        type: 'php',
        target: 'php',
        sources: [
            '*.php',
            '**/*.php',
            '!vendor/',
            '!vendor/**',
            '!dist/',
            '!dist/**',
            '!node_modules/',
            '!node_modules/**',
            '!index.php',
            '!**/index.php',
        ],
    },
];

/**
 * Custom error handler
 */
const errorHandler = function () {
    return plumber(function (err) {
        notify.onError({
            title: 'Gulp error in ' + err.plugin,
            message: err.toString()
        })(err);
    });
};

/**
 * Script taks handler
 */
const scriptsHandler = function (asset, isMinify) {
    const srcParam = asset.sources.map(function (sources) {
        const sourcesDir = asset.sourcesDir || '';
        return sourcesDir + sources;
    });

    return gulp.src(srcParam)
        .pipe(errorHandler())
        .pipe(concat(asset.target + '.js'))
        .pipe(gulpif(asset.isIife, iife({
            useStrict: true,
            trimCode: true,
            prependSemicolon: false,
            bindThis: false,
            params: ['$'],
            args: ['jQuery']
        })))
        .pipe(gulpif(asset.isPrefixed, rename({
            prefix: prefix + '-',
        })))
        .pipe(gulp.dest(asset.targetDir))
        .pipe(gulpif(isMinify, rename({
            suffix: '.min',
        })))
        .pipe(gulpif(asset.isSourceMap, sourcemaps.init()))
        .pipe(gulpif(isMinify, uglify()))
        .pipe(gulpif(asset.isSourceMap, sourcemaps.write()))
        .pipe(gulpif(isMinify, gulp.dest(asset.targetDir)));
}

/**
 * Style taks handler
 */
const stylesHandler = function (asset, isMinify) {
    const srcParam = asset.sources.map(function (sourcesFile) {
        const sourcesDir = asset.sourcesDir || '';
        return sourcesDir + sourcesFile;
    });

    return gulp.src(srcParam)
        .pipe(errorHandler())
        .pipe(gulpif(asset.isSourceMap, sourcemaps.init()))
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer(
            'last 2 version',
            '> 1%',
            'safari 5',
            'ie 8',
            'ie 9',
            'opera 12.1',
            'ios 6',
            'android 4'))
        .pipe(gulpif(asset.isPrefixed, rename({
            prefix: prefix + '-',
        })))
        .pipe(gulp.dest(asset.targetDir))
        .pipe(gulpif(isMinify, rename({
            suffix: '.min',
        })))
        .pipe(gulpif(isMinify, cleanCSS({
            compatibility: 'ie8',
        })))
        .pipe(gulpif(asset.isSourceMap, sourcemaps.write()))
        .pipe(gulpif(isMinify, gulp.dest(asset.targetDir)))
        .pipe(gulpif(!isMinify, browserSync.stream()));
}

/**
 * PHPCS taks handler
 */
const phpcsHandler = function (asset) {
    const srcParam = asset.sources.map(function (sourcesFile) {
        const sourcesDir = asset.sourcesDir || '';
        return sourcesDir + sourcesFile;
    });

    const config = Object.assign({}, asset.config, {
        bin: '/usr/local/bin/phpcs',
        standard: 'WordPress',
        warningSeverity: 0,
    });

    return gulp.src(srcParam)
        .pipe(errorHandler())
        .pipe(phpcs(config))
        .pipe(phpcs.reporter('log'));
}

/**
 * Internationalization taks handler
 */
const i18nHandler = function (asset) {
    const srcParam = asset.sources.map(function (sourcesFile) {
        const sourcesDir = asset.sourcesDir || '';
        return sourcesDir + sourcesFile;
    });

    const config = Object.assign({}, asset.config, {
        domain: prefix,
        package: project,
    });

    return gulp.src(srcParam)
        .pipe(wpPot(config))
        .pipe(gulp.dest('languages/' + config.domain + '.pot'));
}

/**
 * Build tasks list
 */
const tasksListBuild = [];

assets.forEach(function (asset) {
    /**
     * Minify Scripts Task
     */
    if (asset.type === 'scripts') {
        const taskName = asset.target + '-scripts-minify';

        gulp.task(taskName, function () {
            return scriptsHandler(asset, true);
        });

        tasksListBuild.push(taskName);
    }

    /**
     * Minify Styles Task
     */
    if (asset.type === 'styles') {
        const taskName = asset.target + '-styles-minify';

        gulp.task(taskName, function () {
            return stylesHandler(asset, true);
        });

        tasksListBuild.push(taskName);
    }

    /**
     * Internationalization Task
     */
    if (asset.type === 'php') {
        const taskName = asset.target + '-i18n';

        gulp.task(taskName, function () {
            return i18nHandler(asset);
        });

        tasksListBuild.push(taskName);
    }
});

/**
 * Build task
 */
gulp.task('build', tasksListBuild);

/**
 * Default tasks list
 */
const tasksListDefault = [];

assets.forEach(function (asset) {
    /**
     * Scripts Task
     */
    if (asset.type === 'scripts') {
        const taskName = asset.target + '-scripts';

        gulp.task(taskName, function () {
            return scriptsHandler(asset);
        });

        tasksListDefault.push(taskName);
    }

    /**
     * Styles Task
     */
    if (asset.type === 'styles') {
        const taskName = asset.target + '-styles';

        gulp.task(taskName, function () {
            return stylesHandler(asset, false);
        });

        tasksListDefault.push(taskName);
    }

    /**
     * PHPCS Task
     */
    if (asset.type === 'php') {
        const taskName = asset.target + '-phpcs';

        gulp.task(taskName, function () {
            return phpcsHandler(asset);
        });

        tasksListDefault.push(taskName);
    }
});


/**
 * Default task
 */
gulp.task('default', tasksListDefault, function () {
    if (argv.hasOwnProperty('proxy')) {
        browserSync.init({
            proxy: argv.proxy
        });
    }

    assets.forEach(function (asset) {
        /**
         * Watch styles sources files
         */
        if (asset.type === 'styles') {
            const watchStylesSrc = asset.sources.map(function (sourcesFile) {
                const sourcesDir = asset.sourcesDir || '';
                return sourcesDir + sourcesFile;
            });

            gulp.watch(watchStylesSrc, [asset.target + '-styles']);
        }

        /**
         * Watch scripts sources files
         */
        if (asset.type === 'scripts') {
            const watchScriptsSrc = asset.sources.map(function (sourcesFile) {
                const sourcesDir = asset.sourcesDir || '';
                return sourcesDir + sourcesFile;
            });

            gulp.watch(watchScriptsSrc, [asset.target + '-scripts']).on('change', function () {
                if (argv.hasOwnProperty('proxy')) {
                    browserSync.reload();
                }
            });
        }

        /**
         * Watch php sources files
         */
        if (asset.type === 'php') {
            const watchSrc = asset.sources.map(function (sourcesFile) {
                const sourcesDir = asset.sourcesDir || '';
                return sourcesDir + sourcesFile;
            });

            gulp.watch(watchSrc, [asset.target + '-phpcs']).on('change', function () {
                if (argv.hasOwnProperty('proxy')) {
                    browserSync.reload();
                }
            });
        }
    });
});

gulp.task('bump', function () {
    var sources = [
        {
            file: ['./README.txt'],
            config: {
                key: "Stable tag",
                type: argv.hasOwnProperty('type') ? argv.type : 'patch',
            },
        },
        {
            file: ['./woongkir.php'],
            config: {
                key: "Version",
                type: argv.hasOwnProperty('type') ? argv.type : 'patch',
            },
        },
        {
            file: ['./package.json'],
            config: {
                key: "version",
                type: argv.hasOwnProperty('type') ? argv.type : 'patch',
            },
        },
    ];

    sources.forEach(function (source) {
        gulp.src(source.file)
            .pipe(bump(source.config))
            .pipe(gulp.dest('./'));
    });
});

// Export task
gulp.task('dist', ['build'], function () {
    gulp.src([
        './**',
        '!dist/',
        '!dist/**',
        '!node_modules/',
        '!node_modules/**',
        '!assets/src/',
        '!assets/src/**',
        '!gulpfile.js',
        '!package-lock.json',
        '!package.json'
    ]).pipe(gulp.dest('./dist'));
});