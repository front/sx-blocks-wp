const path = require('path');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

const cleanBuild = new CleanWebpackPlugin(['build']);
const extractCSS = new ExtractTextPlugin('style.css');
const extractEditor = new ExtractTextPlugin('editor.css');

module.exports = {
  devtool: 'source-map',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'build'),
    filename: 'index.js',
  },
  module: {
    rules: [
      {
        test: /.js$/,
        use: 'babel-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.s?css$/,
        // exclude: /node_modules/,
        exclude: [/node_modules/, /editor\.s?css$/],
        use: extractCSS.extract(['css-loader', 'sass-loader']),
      },
      {
        test: /editor\.s?css$/,
        exclude: /node_modules/,
        use: extractEditor.extract(['css-loader', 'sass-loader']),
      },
      {
        test: /\.(png|svg|jpg|gif)$/,
        loader: 'url-loader',
      },
    ],
  },
  mode: 'production',
  plugins: [
    cleanBuild,
    extractCSS,
    extractEditor,
  ],
};
