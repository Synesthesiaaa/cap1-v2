const path = require('path');

module.exports = {
  entry: {
    main: './js/main.js',
    ticketForm: './js/ticket_form.js',
    ticketMonitor: './js/ticket_monitor.js'
  },
  output: {
    path: path.resolve(__dirname, 'dist/js'),
    filename: '[name].bundle.js',
    clean: true
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      }
    ]
  },
  resolve: {
    extensions: ['.js']
  },
  devtool: 'source-map',
  mode: 'development'
};
