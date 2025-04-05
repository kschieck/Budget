const gulp = require("gulp");
const clean = require("gulp-clean");
const zip = require("gulp-zip");
const shell = require("gulp-shell");

const paths = {
    client: "client",
    server: "server",
    output: "build"
};

// Clean previous build
gulp.task("clean", function () {
    return gulp.src(paths.output, { allowEmpty: true, read: false }).pipe(clean());
});

// Build client
gulp.task("build-client", shell.task([
    `cd ${paths.client} && npm install && npm run build`
]));

// Copy client build files
gulp.task("copy-client", function () {
    return gulp.src(`${paths.client}/build/**/*`).pipe(gulp.dest(`${paths.output}/public`));
});

// Copy server files (only necessary ones)
gulp.task("copy-server", function () {
    return gulp.src([
        `${paths.server}/**/*`,
        `!${paths.server}/**/*.template` // Exclude node_modules
    ], { base: paths.server }).pipe(gulp.dest(`${paths.output}/public`));
});

// Install server dependencies in the output folder
gulp.task("install-server-dependencies", shell.task([
    `cd ${paths.output}/server && npm install --production`
]));

// Create a zip file
gulp.task("zip", function () {
    return gulp.src(`${paths.output}/**/*`)
        .pipe(zip("deployment.zip"))
        .pipe(gulp.dest("."));
});

// Clean previous build (client)
gulp.task("postbuild-clean-client", function () {
    return gulp.src(`${paths.client}/build`, { allowEmpty: true, read: false }).pipe(clean());
});

// Clean previous build folder
gulp.task("postbuild-clean", function () {
    return gulp.src(paths.output, { allowEmpty: true, read: false }).pipe(clean());
});

// Define the default task to run all tasks in sequence
gulp.task("default", gulp.series(
    "clean",
    "build-client",
    "copy-client",
    "postbuild-clean-client",
    "copy-server",
    "zip",
    "postbuild-clean"
));

// Define the default task to run all tasks in sequence
gulp.task("build-no-zip", gulp.series(
    "clean",
    "build-client",
    "copy-client",
    "copy-server",
    "postbuild-clean-client",
));