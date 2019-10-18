const path = require("path");

module.exports = {
    mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
    devtool: process.env.NODE_ENV == 'production' ? false : 'cheap-eval-source-map',
    entry: {
        'customCheckout': [path.resolve(__dirname, "./views/babel/customCheckout.js")]
    },
    output: {
        path: path.resolve(__dirname, "./views/js"),
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                        plugins: ['@babel/plugin-transform-runtime']
                    }
                }
            }
        ]
    }
};
