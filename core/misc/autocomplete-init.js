/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal, drupalSettings, A11yAutocomplete, Popper, once) {
  Drupal.Autocomplete = {};
  Drupal.Autocomplete.instances = {};
  Drupal.Autocomplete.defaultOptions = {
    inputClass: 'ui-autocomplete-input',
    ulClass: 'ui-menu ui-widget ui-widget-content ui-autocomplete ui-front',
    loadingClass: 'ui-autocomplete-loading',
    itemClass: 'ui-menu-item-wrapper',
    createLiveRegion: false,
    displayLabels: false
  };

  Drupal.Autocomplete.initialize = function (autocompleteInput) {
    var options = Drupal.Autocomplete.defaultOptions || {};

    if (!Object.hasOwnProperty('messages')) {
      options.messages = {};
    }

    options.messages.inputAssistiveHint = Drupal.t('When autocomplete results are available use up and down arrows to review and enter to select.  Touch device users, explore by touch or with swipe gestures.');
    options.liveRegion = false;

    function autocompleteResultsMessage(count) {
      var maxItems = this.options.maxItems;

      if (count === 0) {
        return Drupal.t('No results found');
      }

      return Drupal.formatPlural(count, 'There is one result available.', maxItems <= this.totalSuggestions ? 'There are at least @count results available. Type additional characters to refine your search.' : 'There are @count results available.');
    }

    function autocompleteHighlightMessage(item) {
      return Drupal.t('@item @count of @total is highlighted', {
        '@item': item.innerText,
        '@count': item.getAttribute('aria-posinset'),
        '@total': this.ul.children.length
      });
    }

    function autocompleteSendToLiveRegion(message) {
      Drupal.announce(message, 'assertive');
    }

    var id = autocompleteInput.getAttribute('id');
    Drupal.Autocomplete.instances[id] = new A11yAutocomplete(autocompleteInput, options);
    var instance = Drupal.Autocomplete.instances[id];
    instance.resultsMessage = autocompleteResultsMessage;
    instance.sendToLiveRegion = autocompleteSendToLiveRegion;
    instance.highlightMessage = autocompleteHighlightMessage;
    instance.input.addEventListener('autocomplete-destroy', function (e) {
      delete Drupal.Autocomplete.instances[e.detail.autocomplete.input.getAttribute('id')];
    });
  };

  Drupal.behaviors.autocomplete = {
    attach: function attach(context) {
      once('autocomplete-init', 'input.form-autocomplete').forEach(function (autocompleteInput) {
        if (!autocompleteInput.hasAttribute('data-autocomplete-cardinality')) {
          autocompleteInput.setAttribute('data-autocomplete-cardinality', '-1');
        }

        Drupal.Autocomplete.initialize(autocompleteInput);

        if (!autocompleteInput.hasAttribute('data-drupal-10-autocomplete')) {
          Drupal.Autocomplete.jqueryUiShimInit(autocompleteInput);
        }
      });
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        context.querySelectorAll('input.form-autocomplete').forEach(function (input) {
          var id = input.getAttribute('id');
          Drupal.Autocomplete.instances[id].destroy();
        });
      }
    }
  };
})(Drupal, drupalSettings, A11yAutocomplete, Popper, once);