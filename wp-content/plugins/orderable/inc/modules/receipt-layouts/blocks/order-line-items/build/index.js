/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./inc/modules/receipt-layouts/blocks/order-line-items/src/edit.js":
/*!*************************************************************************!*\
  !*** ./inc/modules/receipt-layouts/blocks/order-line-items/src/edit.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./editor.scss */ "./inc/modules/receipt-layouts/blocks/order-line-items/src/editor.scss");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);






function LineItem({
  productName,
  price,
  showPrices,
  showCheckboxes,
  showMetaData
}) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "orderable-order-line-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, showPrices && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createInterpolateElement)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('1× <product/> <price/>', 'orderable'), {
    product: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      style: {
        marginLeft: '5pt',
        display: 'inline-flex',
        flexDirection: 'column'
      }
    }, productName, showMetaData && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Size: medium', 'orderable'))),
    price: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      style: {
        marginLeft: '15pt'
      }
    }, price)
  }), !showPrices && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createInterpolateElement)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('1× <product/>', 'orderable'), {
    product: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      style: {
        marginLeft: '5pt',
        display: 'inline-flex',
        flexDirection: 'column'
      }
    }, productName, showMetaData && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Size: medium', 'orderable')))
  })), showCheckboxes && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    style: {
      width: '13.5pt',
      height: '13.5pt',
      border: '1px solid #111111'
    }
  }));
}
function Edit({
  attributes,
  setAttributes
}) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Content', 'orderable')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show label', 'orderable'),
    checked: attributes.showLabel,
    onChange: value => setAttributes({
      showLabel: value
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Label', 'orderable'),
    value: attributes.label,
    onChange: value => setAttributes({
      label: value
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show prices', 'orderable'),
    checked: attributes.showPrices,
    onChange: value => setAttributes({
      showPrices: value
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show checkboxes', 'orderable'),
    checked: attributes.showCheckboxes,
    onChange: value => setAttributes({
      showCheckboxes: value
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Show item meta data', 'orderable'),
    checked: attributes.showMetaData,
    onChange: value => setAttributes({
      showMetaData: value
    })
  }))), attributes.showLabel && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "wp-block-orderable-receipt-layouts__label"
  }, attributes.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(LineItem, {
    productName: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Spicy Marinated Olives', 'orderable'),
    price: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('$25.00', 'orderable'),
    showPrices: attributes.showPrices,
    showCheckboxes: attributes.showCheckboxes,
    showMetaData: attributes.showMetaData
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(LineItem, {
    productName: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Hamburger', 'orderable'),
    price: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('$18.00', 'orderable'),
    showPrices: attributes.showPrices,
    showCheckboxes: attributes.showCheckboxes,
    showMetaData: attributes.showMetaData
  }));
}

/***/ }),

/***/ "./inc/modules/receipt-layouts/blocks/order-line-items/src/editor.scss":
/*!*****************************************************************************!*\
  !*** ./inc/modules/receipt-layouts/blocks/order-line-items/src/editor.scss ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**************************************************************************!*\
  !*** ./inc/modules/receipt-layouts/blocks/order-line-items/src/index.js ***!
  \**************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./inc/modules/receipt-layouts/blocks/order-line-items/src/edit.js");



(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('orderable/order-line-items', {
  /**
   * @see ./edit.js
   */
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"],
  icon: {
    src: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      viewBox: "0 0 20 15"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fillRule: "evenodd",
      clipRule: "evenodd",
      fill: "#BD47F5",
      d: "M15.6129 13.6639C18.6362 11.6975 20.7599 7.52818 19.7423 4.61553C18.7247 1.71768 14.5511 0.0765439 10.1415 0.00261896C5.7467 -0.071306 1.1159 1.42198 0.186796 4.20155C-0.75706 6.98113 2.01552 11.0618 5.36326 13.2204C8.69625 15.379 12.6044 15.6303 15.6129 13.6639ZM7.10944 2.68222C6.69369 2.68222 6.35667 3.01988 6.35667 3.43641C6.35667 3.85293 6.69369 4.1906 7.10944 4.1906H10.5044L10.5036 4.19159C10.8969 4.21733 11.2078 4.54442 11.2078 4.94415C11.2078 5.36068 10.8702 5.69834 10.4537 5.69834H9.7826L9.78247 5.69897H4.34928C3.93354 5.69897 3.59651 6.03664 3.59651 6.45316C3.59651 6.86969 3.93354 7.20735 4.34928 7.20735H10.4848C10.8869 7.2237 11.2078 7.55483 11.2078 7.96091C11.2078 8.36064 10.8969 8.68774 10.5037 8.71347L10.5055 8.71573H6.10574C5.69 8.71573 5.35297 9.0534 5.35297 9.46992C5.35297 9.88645 5.69 10.2241 6.10574 10.2241H13.8007C13.8251 10.2241 13.8492 10.223 13.8729 10.2207C16.018 10.1438 17.7318 8.48614 17.7318 6.45253C17.7318 4.36989 15.9344 2.68158 13.7171 2.68158H13.7162L7.10944 2.68222ZM15.0192 6.45253C15.0192 6.87904 14.599 7.51057 13.7171 7.51057C12.8351 7.51057 12.415 6.87904 12.415 6.45253C12.415 6.02602 12.8351 5.39449 13.7171 5.39449C14.599 5.39449 15.0192 6.02602 15.0192 6.45253ZM12.3659 11.2742C12.4644 11.5671 12.2588 11.9864 11.9662 12.1841C11.675 12.3818 11.2968 12.3566 10.9742 12.1395C10.6502 11.9224 10.3819 11.5121 10.4732 11.2326C10.5631 10.9531 11.0113 10.8029 11.4367 10.8103C11.8634 10.8178 12.2674 10.9828 12.3659 11.2742ZM13.6518 12.1841C13.3592 11.9864 13.1536 11.5671 13.2521 11.2742C13.3506 10.9828 13.7546 10.8178 14.1813 10.8103C14.6067 10.8029 15.0549 10.9531 15.1448 11.2326C15.2361 11.5121 14.9678 11.9224 14.6438 12.1395C14.3212 12.3566 13.943 12.3818 13.6518 12.1841Z"
    }))
  }
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map