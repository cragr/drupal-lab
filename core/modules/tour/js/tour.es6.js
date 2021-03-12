/**
 * @file
 * Attaches behaviors for the Tour module's toolbar tab.
 */

(function ($, Drupal, document, drupalSettings) {
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
          const modelId = btoa(Math.random()).substring(0, 12);
          const modelKey = `tourModel-${modelId}`;
          const $tour = $(context).find('ol#tour');
          if ($tour.length) {
            $('#toolbar-tab-tour').removeClass('hidden');
            const $toggleButton = $('#toolbar-tab-tour').find('button');

            drupalSettings[modelKey] = {
              $el: $('#toolbar-tab-tour'),
              tour: $tour,
              isActive: /tour=?/i.test(queryString),
              activeTour: [],
            };

            const renderTour = () => {
              const tourSettings = drupalSettings[modelKey];
              // Render the visibility.
              tourSettings.$el.toggleClass(
                'hidden',
                tourSettings.tour.length === 0,
              );
              // Render the state.
              const { isActive } = tourSettings;
              tourSettings.$el
                .find('button')
                .toggleClass('is-active', isActive)
                .prop('aria-pressed', isActive);
              return this;
            };

            const toggleTour = () => {
              const tourSettings = drupalSettings[modelKey];
              const _removeIrrelevantTourItems = () => {
                const $document = $(document);
                let removals = false;
                const tips = /tips=([^&]+)/.exec(queryString);
                // eslint-disable-next-line func-names
                tourSettings.tour.find('li').each(function () {
                  const $this = $(this);
                  const itemId = $this.attr('data-id');
                  const itemClass = $this.attr('data-class');
                  // If the query parameter 'tips' is set, remove all tips that don't
                  // have the matching class.
                  if (tips && !$(this).hasClass(tips[1])) {
                    removals = true;
                    $this.remove();
                    return;
                  }
                  // Remove tip from the DOM if there is no corresponding page element.
                  if (
                    (!itemId && !itemClass) ||
                    (itemId && $document.find(`#${itemId}`).length) ||
                    (itemClass && $document.find(`.${itemClass}`).length)
                  ) {
                    return;
                  }
                  removals = true;
                  $this.remove();
                });

                // If there were removals, we'll have to do some clean-up.
                if (removals) {
                  const total = tourSettings.tour.find('li').length;
                  if (!total) {
                    Drupal.modelSet('render-tour', modelKey, {
                      tour: [],
                    });
                  } else {
                    tourSettings.tour
                      .find('li')
                      // Rebuild the progress data.
                      // eslint-disable-next-line func-names
                      .each(function (index) {
                        const progress = Drupal.t('!tour_item of !total', {
                          '!tour_item': index + 1,
                          '!total': total,
                        });
                        $(this).find('.tour-progress').text(progress);
                      })
                      // Update the last item to have "End tour" as the button.
                      .eq(-1)
                      .attr('data-text', Drupal.t('End tour'));
                  }
                }
              };

              if (tourSettings.isActive) {
                _removeIrrelevantTourItems();
                const close = Drupal.t('Close');
                if (tourSettings.tour.find('li').length) {
                  $tour.joyride({
                    autoStart: true,
                    postRideCallback() {
                      Drupal.modelSet('toggle-tour', modelKey, {
                        isActive: false,
                      });
                    },
                    // HTML segments for tip layout.
                    template: {
                      link: `<a href="#close" class="joyride-close-tip" aria-label="${close}">&times;</a>`,
                      button:
                        '<a href="#" class="button button--primary joyride-next-tip"></a>',
                    },
                  });
                  Drupal.modelSet('render-tour', modelKey, {
                    activeTour: $tour,
                  });
                }
              } else if (tourSettings.activeTour.length) {
                tourSettings.activeTour.joyride('destroy');
                Drupal.modelSet('render-tour', modelKey, {
                  activeTour: [],
                });
                tourSettings.activeTour = [];
              }
            };

            renderTour();

            $toggleButton.get(0).addEventListener('click', (e) => {
              e.preventDefault();
              Drupal.modelSet('toggle-tour', modelKey, {
                isActive: !drupalSettings[modelKey].isActive,
              });
            });

            Drupal.listenTo('toggle-tour', toggleTour);
            Drupal.listenTo('toggle-tour', renderTour);
            Drupal.listenTo('render-tour', renderTour);
          }
        });
    },
  };
})(jQuery, Drupal, document, drupalSettings);
