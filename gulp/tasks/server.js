
export const server = (done) => {
    app.plugins.browsersync.init({
        proxy: "http://aulutsbw.beget.tech/",
        serveStatic: ['.'],
        files: ["./**/*.css", "./**/*.js", "./**/*.php"],
        notify: false,
        online: true
    });
    done();
};