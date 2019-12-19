### To build
1. Make sure [NodeJS](https://nodejs.org/en/download) is installed. 
2. Use NodeJS to install the React and LESS transpilers. In this project 
we use [Babel](https://babeljs.io/) and 
[Webpack](https://webpack.js.org/) for this. These dependencies 
are already preconfigured in [package.json](./package.json). 
Simply run `npm install` from this directory to install them.
3. The build script is also pre-configured for NodeJS in 
[package.json](./package.json). Run `npm run build` to build
the React- and LESS-based pages.

NOTE: For development, replace `npm run build` in step 3 with
`npm run dev`. Webpack will watch the files as you edit them &
recompile on the fly.
