const path = require("path");

module.exports = {
  context: path.resolve(__dirname, "src"),
  entry: {
    garden: path.resolve(__dirname, "src", "garden", "main.jsx"),
    header: path.resolve(__dirname, "src", "header", "main.jsx")
  },
  output: {
    path: path.resolve(__dirname, "dist"),
    libraryTarget: "var",
    library: "OregonFlora"
  },
  watch: true,
  watchOptions: {
    ignored: /node_modules/
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader"
        }
      }
    ]
  }
};