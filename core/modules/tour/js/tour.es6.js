/**
 * @file
 * Attaches behaviors for the Tour module's toolbar tab.
 */

(($, Backbone, Drupal, settings, document, Shepherd) => {
  const queryString = decodeURI(window.location.search);

  /**
   * Attaches the tour's toolbar tab behavior.
   *
   * It uses the query string for:
   * - tour: When ?tour=1 is present, the tour will start automatically after
   *   the page has loaded.
   * - tips: Pass ?tips=class in the url to filter the available tips to the
   *   subset which match the given class.
   *
   * @example
   * http://example.com/foo?tour=1&tips=bar
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach tour functionality on `tour` events.
   */
  Drupal.behaviors.tour = {
    attach(context) {
      $('body')
        .once('tour')
        .each(() => {
          const model = new Drupal.tour.models.StateModel();
          // eslint-disable-next-line no-new
          new Drupal.tour.views.ToggleTourView({
            el: $(context).find('#toolbar-tab-tour'),
            model,
          });

          model
            // Allow other scripts to respond to tour events.
            .on('change:isActive', (tourModel, isActive) => {
              $(document).trigger(
                isActive ? 'drupalTourStarted' : 'drupalTourStopped',
              );
            });
          // Initialization: check whether a tour is available on the current
          // page.
          if (settings.tour) {
            model.set('tour', settings.tour);
          }

          // Start the tour immediately if toggled via query string.
          if (/tour=?/i.test(queryString)) {
            model.set('isActive', true);
          }
        });
    },
  };

  /**
   * @namespace
   */
  Drupal.tour = Drupal.tour || {
    /**
     * @namespace Drupal.tour.models
     */
    models: {},

    /**
     * @namespace Drupal.tour.views
     */
    views: {},
  };

  /**
   * Backbone Model for tours.
   *
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.tour.models.StateModel = Backbone.Model.extend(
    /** @lends Drupal.tour.models.StateModel# */ {
      /**
       * @type {object}
       */
      defaults: /** @lends Drupal.tour.models.StateModel# */ {
        /**
         * Indicates whether the Drupal root window has a tour.
         *
         * @type {Array}
         */
        tour: [],

        /**
         * Indicates whether the tour is currently running.
         *
         * @type {bool}
         */
        isActive: false,

        /**
         * Indicates which tour is the active one (necessary to cleanly stop).
         *
         * @type {Array}
         */
        activeTour: [],
      },
    },
  );

  Drupal.tour.views.ToggleTourView = Backbone.View.extend(
    /** @lends Drupal.tour.views.ToggleTourView# */ {
      /**
       * @type {object}
       */
      events: { click: 'onClick' },

      /**
       * Handles edit mode toggle interactions.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        this.listenTo(this.model, 'change:tour change:isActive', this.render);
        this.listenTo(this.model, 'change:isActive', this.toggleTour);
      },

      /**
       * {@inheritdoc}
       *
       * @return {Drupal.tour.views.ToggleTourView}
       *   The `ToggleTourView` view.
       */
      render() {
        // Render the visibility.
        this.$el.toggleClass('hidden', this._getTour().length === 0);
        // Render the state.
        const isActive = this.model.get('isActive');
        this.$el
          .find('button')
          .toggleClass('is-active', isActive)
          .attr('aria-pressed', isActive);
        return this;
      },

      /**
       * Model change handler; starts or stops the tour.
       */
      toggleTour() {
        if (this.model.get('isActive')) {
          this._removeIrrelevantTourItems(this._getTour());
          const tourItems = this.model.get('tour');
          const that = this;

          if (tourItems.length) {
            const shepherdTour = new Shepherd.Tour(settings.tourShepherdConfig);
            shepherdTour.on('cancel', () => {
              that.model.set('isActive', false);
            });
            shepherdTour.on('complete', () => {
              that.model.set('isActive', false);
            });

            tourItems.forEach((step, index) => {
              const tourItemOptions = {
                title: step.title ? Drupal.checkPlain(step.title) : null,
                text: () =>
                  `<p>${step.body}</p><div class="tour-progress">${step.counter}</div>`,
                attachTo: {
                  element: step.selector,
                  on: step.location ? step.location : 'bottom',
                },
                buttons: [
                  {
                    classes: 'button button--primary',
                    text: step.cancelText ? step.cancelText : Drupal.t('Next'),
                    action: step.cancelText
                      ? shepherdTour.cancel
                      : shepherdTour.next,
                  },
                ],
                classes: step.classes,
                // @todo joyride_content_container_name  can be removed when the Stable9
                //   theme is removed from core. This only exists to provide Joyride
                //   backwards compatibility.
                joyride_content_container_name:
                step.joyride_content_container_name,
                index,
              };

              // When Stable or Stable 9 are part of the active theme, the
              // Drupal.tour.convertToJoyrideMarkup() function is available.
              // This function converts Shepherd markup to Joyride markup,
              // facilitating the use of the Shepherd library that is backwards
              // compatible with customizations intended for Joyride.
              if (Drupal.tour.hasOwnProperty('convertToJoyrideMarkup')) {
                tourItemOptions.when = {
                  show() {
                    Drupal.tour.convertToJoyrideMarkup(shepherdTour);
                  },
                };
              }
              shepherdTour.addStep(tourItemOptions);
            });
            shepherdTour.start();
            this.model.set({ isActive: true, activeTour: shepherdTour });
          }
        } else {
          this.model.get('activeTour').cancel();
          this.model.set({ isActive: false, activeTour: [] });
        }
      },

      /**
       * Toolbar tab click event handler; toggles isActive.
       *
       * @param {jQuery.Event} event
       *   The click event.
       */
      onClick(event) {
        this.model.set('isActive', !this.model.get('isActive'));
        event.preventDefault();
        event.stopPropagation();
      },

      /**
       * Gets the tour.
       *
       * @return {array}
       *   An array of Shepherd tour item objects.
       */
      _getTour() {
        return this.model.get('tour');
      },

      /**
       * Removes tour items for elements that don't have matching page elements.
       *
       * Or that are explicitly filtered out via the 'tips' query string.
       *
       * @example
       * <caption>This will filter out tips that do not have a matching
       * page element or don't have the "bar" class.</caption>
       * http://example.com/foo?tips=bar
       *
       * @param {array} tourItems
       *   An array containing tour item objects.
       */
      _removeIrrelevantTourItems(tourItems) {
        const tips = /tips=([^&]+)/.exec(queryString);
        const filteredTour = tourItems.filter((tourItem) => {
          // If the query parameter 'tips' is set, remove all tips that don't
          // have the matching class.
          if (tips && tourItem.class.indexOf(tips[1]) === -1) {
            return false;
          }

          // If a selector is configured but there isn't a matching element,
          // return false.
          return !(
            tourItem.selector && !document.querySelector(tourItem.selector)
          );
        });

        // If there tours filtered, we'll have to update model.
        if (tourItems.length !== filteredTour.length) {
          filteredTour.forEach((filteredTourItem, filteredTourItemId) => {
            filteredTour[filteredTourItemId].counter = Drupal.t(
              '!tour_item of !total',
              {
                '!tour_item': filteredTourItemId + 1,
                '!total': filteredTour.length,
              },
            );

            if (filteredTourItemId === filteredTour.length - 1) {
              filteredTour[filteredTourItemId].cancelText = Drupal.t(
                'End tour',
              );
            }
          });
          this.model.set('tour', filteredTour);
        }
      },
    },
  );
})(jQuery, Backbone, Drupal, drupalSettings, document, window.Shepherd);
