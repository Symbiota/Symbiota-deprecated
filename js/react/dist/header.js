var OregonFlora =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./header/main.jsx");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./header/main.jsx":
/*!*************************!*\
  !*** ./header/main.jsx ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("function _typeof(obj) { if (typeof Symbol === \"function\" && typeof Symbol.iterator === \"symbol\") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === \"function\" && obj.constructor === Symbol && obj !== Symbol.prototype ? \"symbol\" : typeof obj; }; } return _typeof(obj); }\n\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nfunction _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === \"object\" || typeof call === \"function\")) { return call; } return _assertThisInitialized(self); }\n\nfunction _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError(\"this hasn't been initialised - super() hasn't been called\"); } return self; }\n\nfunction _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }\n\nfunction _inherits(subClass, superClass) { if (typeof superClass !== \"function\" && superClass !== null) { throw new TypeError(\"Super expression must either be null or a function\"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }\n\nfunction _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }\n\nfunction getScollPos() {\n  return document.body.scrollTop || document.documentElement.scrollTop;\n}\n\nfunction ImageLink(props) {\n  return React.createElement(\"a\", {\n    href: props.href\n  }, React.createElement(\"img\", {\n    src: props.src,\n    alt: props.src\n  }));\n}\n\nvar HeaderApp =\n/*#__PURE__*/\nfunction (_React$Component) {\n  _inherits(HeaderApp, _React$Component);\n\n  function HeaderApp(props) {\n    var _this;\n\n    _classCallCheck(this, HeaderApp);\n\n    _this = _possibleConstructorReturn(this, _getPrototypeOf(HeaderApp).call(this, props));\n    _this.state = {\n      isCollapsed: getScollPos() > 80,\n      scrollLock: false\n    };\n    return _this;\n  }\n\n  _createClass(HeaderApp, [{\n    key: \"componentDidMount\",\n    value: function componentDidMount() {\n      var _this2 = this;\n\n      var siteHeader = document.getElementById(\"site-header\");\n      siteHeader.addEventListener(\"transitionstart\", function () {\n        _this2.setState({\n          scrollLock: true\n        });\n      });\n      siteHeader.addEventListener(\"transitionend\", function () {\n        _this2.setState({\n          scrollLock: false\n        });\n      });\n      siteHeader.addEventListener(\"transitioncancel\", function () {\n        _this2.setState({\n          scrollLock: false\n        });\n      });\n      window.addEventListener(\"scroll\", function () {\n        if (!_this2.state.scrollLock) {\n          _this2.setState({\n            isCollapsed: getScollPos() > 80 + siteHeader.offsetHeight\n          });\n        }\n      });\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      return React.createElement(\"nav\", {\n        id: \"site-header\",\n        style: {\n          backgroundImage: \"url(\".concat(this.props.clientRoot, \"/images/header/OF-Header_May8.png)\")\n        },\n        className: \"navbar navbar-expand-lg navbar-dark bg-dark site-header \".concat(this.state.isCollapsed ? \"site-header-scroll\" : '')\n      }, React.createElement(\"a\", {\n        className: \"navbar-brand\",\n        href: \"\".concat(this.props.clientRoot, \"/\")\n      }, React.createElement(\"img\", {\n        id: \"site-header-logo\",\n        src: this.state.isCollapsed ? \"/images/header/oregonflora-logo-sm.png\" : \"/images/header/oregonflora-logo.png\",\n        alt: \"OregonFlora\"\n      })));\n    }\n  }]);\n\n  return HeaderApp;\n}(React.Component);\n\nmodule.exports = {\n  \"renderHeader\": function renderHeader(props, targetNode) {\n    var component = React.createElement(HeaderApp, props, null);\n    ReactDOM.render(component, targetNode);\n    return component;\n  }\n};\n{\n  /*\n  <!-- Holds dropdowns on mobile -->\n  <button\n   id=\"site-header-navbar-toggler\"\n   className=\"navbar-toggler ml-auto\"\n   type=\"button\"\n   data-toggle=\"collapse\"\n   data-target=\"#site-header-dropdowns\"\n   aria-controls=\"navbarSupportedContent\"\n   aria-expanded=\"false\"\n   aria-label=\"Toggle navigation\">\n    <span className=\"navbar-toggler-icon\"></span>\n  </button>\n  <!-- Dropdowns -->\n  <div id=\"site-header-dropdowns\" className=\"collapse navbar-collapse\">\n   <ul className=\"navbar-nav\">\n     <!-- Explore site -->\n     <li className=\"nav-item dropdown\">\n       <a id=\"explore\" className=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-toggle=\"dropdown\"\n          aria-haspopup=\"true\" aria-expanded=\"false\">\n         Explore Our Site\n       </a>\n       <div className=\"dropdown-menu\" aria-labelledby=\"explore\">\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/spatial/index.php\">Mapping</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key\">Interactive\n           Key</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/projects/index.php\">Plant Inventories</a>\n         <a className=\"dropdown-item\"\n            href=\"<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241\">OSU\n           Herbarium</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/garden/index.php\">Gardening with Natives</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/imagelib/search.php\">Image Search</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/taxa/admin/taxonomydisplay.php\">Taxonomic\n           Tree</a>\n       </div>\n     </li>\n      <!-- Resources -->\n     <li className=\"nav-item dropdown\">\n       <a id=\"resources\" className=\"nav-link dropdown-toggle wht-txt\" href=\"#\" role=\"button\" data-toggle=\"dropdown\"\n          aria-haspopup=\"true\" aria-expanded=\"false\">\n         Resources\n       </a>\n       <div className=\"dropdown-menu\" aria-labelledby=\"resources\">\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/whats-new.php\">What's New</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/newsletters/index.php\">Archived\n           Newsletter</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/links.php\">Links</a>\n       </div>\n     </li>\n      <!-- About -->\n     <li className=\"nav-item dropdown\">\n       <a id=\"about\" className=\"nav-link dropdown-toggle wht-txt\" href=\"#\" role=\"button\" data-toggle=\"dropdown\"\n          aria-haspopup=\"true\" aria-expanded=\"false\">\n         About\n       </a>\n       <div className=\"dropdown-menu\" aria-labelledby=\"about\">\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/mission.php\">Mission and History</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/contact.php\">Contact Info</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/project-participants.php\">Project\n           Participants</a>\n       </div>\n     </li>\n      <!-- Contribute -->\n     <li className=\"nav-item dropdown\">\n       <a id=\"contribute\" className=\"nav-link dropdown-toggle wht-txt\" href=\"#\" role=\"button\"\n          data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n         Contribute\n       </a>\n       <div className=\"dropdown-menu\" aria-labelledby=\"contribute\">\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/donate.php\">Donate</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/volunteer.php\">Volunteer</a>\n         <a className=\"dropdown-item\" href=\"<?php echo $clientRoot; ?>/pages/merchandise.php\">Merchandise</a>\n       </div>\n     </li>\n   </ul>\n  </div>\n  <!-- Dropdowns end -->\n  <!-- Search -->\n  <form\n   className=\"form-inline ml-auto\"\n   name=\"quick-search\"\n   id=\"quick-search\"\n   autoComplete=\"off\"\n   action=\"<?php echo $clientRoot . '/taxa/index.php'?>\">\n   <div className=\"input-group\">\n     <div className=\"dropdown\">\n       <input id=\"search-term\" name=\"taxon\" type=\"text\" className=\"form-control dropdown-toggle\"\n              data-toggle=\"dropdown\" placeholder=\"Search all plants\">\n         <div id=\"autocomplete-results\" className=\"dropdown-menu\" aria-labelledby=\"search-term\">\n           <a className=\"dropdown-item\" onClick=\"document.getElementById('search-term').value = this.innerHTML;\"\n              href=\"#\" />\n           <a className=\"dropdown-item\" onClick=\"document.getElementById('search-term').value = this.innerHTML;\"\n              href=\"#\" />\n           <a className=\"dropdown-item\" onClick=\"document.getElementById('search-term').value = this.innerHTML;\"\n              href=\"#\" />\n           <a className=\"dropdown-item\" onClick=\"document.getElementById('search-term').value = this.innerHTML;\"\n              href=\"#\" />\n           <a className=\"dropdown-item\" onClick=\"document.getElementById('search-term').value = this.innerHTML;\"\n              href=\"#\" />\n         </div>\n     </div>\n     <input\n       id=\"search-btn\"\n       src=\"<?php echo $clientRoot; ?>/images/header/search-white.png\"\n       className=\"mt-auto mb-auto\"\n       type=\"image\" />\n   </div>\n  </form>\n  <!-- Search end -->\n  </nav>\n  <!-- Header end -->\n  */\n}\n\n//# sourceURL=webpack://OregonFlora/./header/main.jsx?");

/***/ })

/******/ });