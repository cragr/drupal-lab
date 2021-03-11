/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

(function ($, Drupal) {
  Drupal.Autocomplete.jqueryUiShimInit = function (autocompleteInput) {
    var id = autocompleteInput.getAttribute('id');
    var instance = Drupal.Autocomplete.instances[id];
    var isContentEditable = instance.input.hasAttribute('contenteditable');
    var isMultiline = instance.input.tagName === 'TEXTAREA' || instance.input.tagName !== 'INPUT' && isContentEditable;
    instance.options.isMultiline = isMultiline;

    if (instance.options.allowRepeatValues === null) {
      instance.options.allowRepeatValues = true;
    }

    if (!instance.input.hasAttribute('data-autocomplete-list-appended')) {
      var listBoxId = instance.ul.getAttribute('id');
      var uiFront = $(autocompleteInput).closest('.ui-front, dialog');
      var appendTo = uiFront.length > 0 ? uiFront[0] : document.querySelector('body');
      appendTo.appendChild(Drupal.Autocomplete.instances[id].ul);
      Drupal.Autocomplete.instances[id].ul = document.querySelector("#".concat(listBoxId));
    }

    Popper.createPopper(instance.input, instance.ul, {
      placement: 'bottom-start'
    });

    function shimmedInputKeyDown(e) {
      if (!['INPUT', 'TEXTAREA'].includes(this.input.tagName) && this.input.hasAttribute('contenteditable')) {
        this.input.value = this.input.textContent;
      }

      var keyCode = e.keyCode;

      if (this.isOpened) {
        if (keyCode === this.keyCode.ESC) {
          this.close();
        }

        if (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP) {
          e.preventDefault();
          this.preventCloseOnBlur = true;
          var selector = keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';
          this.highlightItem(this.ul.querySelector(selector));
        }

        if (keyCode === this.keyCode.RETURN) {
          var active = instance.ul.querySelectorAll('.ui-menu-item-wrapper.ui-state-active');

          if (active.length) {
            e.preventDefault();
          }
        }
      }

      if (this.input.nodeName === 'INPUT' && !this.isOpened && this.options.list.length > 0 && (keyCode === this.keyCode.DOWN || keyCode === this.keyCode.UP)) {
        e.preventDefault();
        this.suggestionItems = this.options.list;
        this.preventCloseOnBlur = true;
        var typed = this.extractLastInputValue();

        if (!typed && this.options.minChars < 1) {
          this.ul.innerHTML = '';
          this.prepareSuggestionList();
        } else {
          this.displayResults();
        }

        if (this.ul.children.length > 0) {
          this.open();
        }

        if (this.isOpened) {
          var _selector = keyCode === this.keyCode.DOWN ? 'li' : 'li:last-child';

          this.highlightItem(this.ul.querySelector(_selector));
        }
      }

      this.removeAssistiveHint();
    }

    function autocompleteFormatSuggestionItem(suggestion, li) {
      var propertyToDisplay = this.options.displayLabels ? 'label' : 'value';
      $(li).data('ui-autocomplete-item', suggestion);
      return "<a tabindex=\"-1\" class=\"ui-menu-item-wrapper\">".concat(suggestion[propertyToDisplay].trim(), "</a>");
    }

    instance.formatSuggestionItem = autocompleteFormatSuggestionItem;
    instance.inputKeyDown = shimmedInputKeyDown;

    if (isContentEditable) {
      instance.getValue = function () {
        return this.input.textContent;
      };

      instance.replaceInputValue = function (element) {
        var itemIndex = element.closest('[data-drupal-autocomplete-item]').getAttribute('data-drupal-autocomplete-item');
        this.selected = this.suggestions[itemIndex];
        var separator = this.separator();

        if (separator.length > 0) {
          var before = this.previousItems(separator);
          this.input.textContent = "".concat(before).concat(element.textContent);
        } else {
          this.input.textContent = element.textContent;
        }
      };
    }

    var closeOnClickOutside = function closeOnClickOutside(event) {
      var menuElement = instance.ul;
      var targetInWidget = event.target === instance.input || event.target === menuElement || $.contains(menuElement, event.target);

      if (!targetInWidget) {
        instance.close();
      }
    };

    instance.ul.addEventListener('mousedown', function (e) {
      e.preventDefault();
    });
    instance.input.addEventListener('autocomplete-open', function (e) {
      document.body.addEventListener('mousedown', closeOnClickOutside);
    });
    instance.input.addEventListener('autocomplete-close', function (e) {
      document.body.removeEventListener('mousedown', closeOnClickOutside);
    });
    instance.input.addEventListener('autocomplete-highlight', function (e) {
      instance.ul.querySelectorAll('.ui-menu-item-wrapper.ui-state-active').forEach(function (element) {
        element.classList.remove('ui-state-active');
      });
      document.activeElement.querySelector('.ui-menu-item-wrapper').classList.add('ui-state-active');
    });
    $(instance.input).unwrap('[data-drupal-autocomplete-wrapper]');
  };

  $.fn.extend({
    autocomplete: function autocomplete() {
      var _this = this;

      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      var id = this.attr('id');
      var optionMapping = {
        autoFocus: 'autoFocus',
        classes: null,
        delay: 'searchDelay',
        disabled: 'disabled',
        minLength: 'minChars',
        position: null,
        source: null
      };

      if (typeof args[0] === 'string') {
        var instance = Drupal.Autocomplete.instances[id];
        var method = args[0];

        switch (method) {
          case 'search':
            instance.input.focus();

            if (typeof args[1] === 'string') {
              if (instance.input.hasAttribute('contenteditable')) {
                instance.input.textContent = args[1];
              }

              instance.input.value = args[1];
            } else if (instance.input.hasAttribute('contenteditable')) {
              instance.input.value = instance.input.textContent;
            }

            if (instance.input.value.length === 0 && instance.options.minChars === 0) {
              instance.suggestionItems = instance.options.list;
              instance.prepareSuggestionList();

              if (instance.ul.children.length === 0) {
                instance.close();
              } else {
                instance.open();
              }

              window.clearTimeout(instance.timeOutId);
              instance.timeOutId = setTimeout(function () {
                return instance.sendToLiveRegion(instance.resultsMessage(instance.ul.children.length));
              }, 1400);
            } else {
              instance.doSearch($.Event('keydown'));
            }

            break;

          case 'widget':
            return $(instance.ul);

          case 'instance':
            return {
              document: $(document),
              element: $(instance.input),
              menu: {
                element: $(instance.ul)
              },
              liveRegion: $(instance.liveRegion),
              bindings: null,
              classesElementLookup: null,
              eventNamespace: null,
              focusable: null,
              hoverable: null,
              isMultiLine: instance.options.isMultiLine,
              isNewMenu: null,
              options: instance.options,
              source: null,
              uuid: null,
              valueMethod: null,
              window: window
            };

          case 'close':
            instance.close();
            break;

          case 'disable':
            this.autocomplete('option', 'disabled', true);
            break;

          case 'enable':
            this.autocomplete('option', 'disabled', false);
            break;

          default:
            if (typeof instance[method] === 'function') {
              instance[method]();
            }

            break;
        }

        if (method === 'option') {
          if (typeof args[2] === 'undefined' && args[1] === 'object') {
            Object.keys(args[1]).forEach(function (key) {
              _this.autocomplete('option', key, args[1][key]);
            });
          }

          if (typeof args[2] !== 'undefined' && typeof args[1] === 'string') {
            var methodName = args[0],
                optionName = args[1],
                optionValue = args[2];
            var listBoxId = instance.ul.getAttribute('id');

            switch (optionName) {
              case 'appendTo':
                var appendTo = null;

                if (typeof optionValue === 'string') {
                  appendTo = document.querySelector(optionValue);
                } else if (optionValue instanceof jQuery) {
                  appendTo = optionValue.length > 0 ? optionValue[0] : null;
                } else {
                  appendTo = optionValue;
                }

                if (!appendTo) {
                  var closestUiFront = $(instance.input).closest('.ui-front, dialog');

                  if (closestUiFront.length > 0) {
                    var _closestUiFront = _slicedToArray(closestUiFront, 1);

                    appendTo = _closestUiFront[0];
                  }
                }

                if (appendTo) {
                  if (!appendTo.contains(instance.ul)) {
                    appendTo.appendChild(instance.ul);
                  }

                  instance.ul = appendTo.querySelector("#".concat(listBoxId));
                }

                instance.input.setAttribute('data-autocomplete-list-appended', true);
                break;

              case 'classes':
                Object.keys(optionValue).forEach(function (key) {
                  if (key === 'ui-autocomplete' || key === 'ui-autocomplete-input') {
                    var element = key === 'ui-autocomplete' ? instance.ul : instance.input;
                    optionValue[key].split(' ').forEach(function (className) {
                      element.classList.add(className);
                    });
                    element.classList.remove(key);
                  }
                });
                break;

              case 'classes.ui-autocomplete':
                optionValue.split(' ').forEach(function (className) {
                  instance.ul.classList.add(className);
                });
                break;

              case 'classes.ui-autocomplete-input':
                optionValue.split(' ').forEach(function (className) {
                  instance.input.classList.add(className);
                });
                break;

              case 'disabled':
                instance.options.disabled = optionValue;
                $(instance.ul).toggleClass('ui-autocomplete-disabled', optionValue);
                break;

              case 'position':
                $(instance.ul).position(_objectSpread({
                  of: instance.input
                }, optionValue));
                break;

              case 'source':
                if (typeof optionValue === 'function') {
                  var overriddenResponse = function overriddenResponse(newList) {
                    instance.options.list = newList;
                    instance.suggestionItems = instance.options.list;
                    instance.displayResults();
                  };

                  instance.doSearch = function () {
                    optionValue({
                      term: instance.extractLastInputValue()
                    }, overriddenResponse);
                  };
                } else if (typeof optionValue === 'string') {
                  try {
                    var list = JSON.parse(optionValue);
                    instance.options.list = list;
                  } catch (e) {
                    instance.options.path = optionValue;
                  }
                } else {
                  Drupal.Autocomplete.instances[id].options.list = optionValue;
                }

                break;

              default:
                if (['change', 'close', 'create', 'focus', 'open', 'response', 'search', 'select'].includes(optionName)) {
                  this.on("autocomplete".concat(optionName), optionValue);
                }

                if (optionMapping.hasOwnProperty(optionName)) {
                  instance.options[optionMapping[optionName]] = optionValue;
                  instance.options[optionName] = optionValue;
                }

                break;
            }
          } else if (typeof args[1] === 'string') {
            return instance.options(args[1]);
          }
        }
      } else {
        Drupal.Autocomplete.initialize(this[0]);
        Drupal.Autocomplete.jqueryUiShimInit(this[0]);

        if (_typeof(args[0]) === 'object') {
          Object.keys(args[0]).forEach(function (key) {
            _this.autocomplete('option', key, args[0][key]);
          });
        }
      }

      return this;
    }
  });
})(jQuery, Drupal);