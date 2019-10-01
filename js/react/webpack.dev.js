const path = require("path");

module.exports = {
  context: path.resolve(__dirname, "src"),
  entry: {
    header: path.resolve(__dirname, "src", "header", "main.jsx"),
    garden: path.resolve(__dirname, "src", "garden", "main.jsx"),
    gardenTaxa: path.resolve(__dirname, "src", "gardenTaxa", "main.jsx")
  },
  output: {
    path: path.resolve(__dirname, "dist")
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