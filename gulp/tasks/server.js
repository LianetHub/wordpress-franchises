
export const server = (done) => {
    app.plugins.browsersync.init({
        proxy: "localhost",
        notify: false,
        port: 3000,
    });
    done();
};