const path = require("path");

module.exports = {
  context: path.resolve(__dirname, "src"),
  entry: { "garden": path.resolve(__dirname, "src", "main.jsx") },
  output: { "path": path.resolve(__dirname, "dist") },
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