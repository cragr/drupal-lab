(($, Drupal, Popper) => {
  Drupal.PopperInstances = {};

  /**
   * Calculates vertical fixed positioning.
   *
   * @param {jQuery} itemBeingPositioned
   *   The element being positioned.
   * @param {object} positionedItemSettings
   *   Offset and position settings for the item being positioned.
   * @param {object}  referenceItemSettings
   *   Offset and position settings for the reference item.
   * @param {object} positionCss
   *   An object of CSS styles that will be applied to the positioned item.
   *
   * @return {{topComp: number, positionCss: object}}
   *   Object with top compensation and position css.
   */
  const calculateVerticalFixedPositioning = (
    itemBeingPositioned,
    positionedItemSettings,
    referenceItemSettings,
    positionCss,
  ) => {
    // Compensation that will be applied to top positioning.
    let topComp = 0;

    // Calculate compensation.
    if (positionedItemSettings.vertical === 'center') {
      // When the positioned item's positioning is on the center axis, the
      // offset is half of its height.
      topComp = -itemBeingPositioned.outerHeight() / 2;
    } else if (
      (positionedItemSettings.vertical === 'top' &&
        referenceItemSettings.vertical === 'bottom') ||
      (positionedItemSettings.vertical === 'bottom' &&
        referenceItemSettings.vertical !== 'bottom')
    ) {
      topComp = -itemBeingPositioned.outerHeight();
    }

    const totalVerticalOffset =
      referenceItemSettings.verticalOffset +
      positionedItemSettings.verticalOffset;

    // Apply vertical positioning.
    if (referenceItemSettings.vertical === 'center') {
      const top =
        document.documentElement.clientHeight / 2 +
        topComp +
        totalVerticalOffset;
      positionCss.top = `${top}px`;
    } else if (
      referenceItemSettings.vertical === 'top' ||
      referenceItemSettings.vertical === 'bottom'
    ) {
      const verticalPosition =
        referenceItemSettings.vertical === 'bottom'
          ? topComp - totalVerticalOffset
          : topComp + totalVerticalOffset;

      positionCss[referenceItemSettings.vertical] = `${verticalPosition}px`;
    }

    return positionCss;
  };

  /**
   * Calculates horizontal fixed positioning.
   *
   * @param {jQuery} itemBeingPositioned
   *   The element being positioned.
   * @param {object} positionedItemSettings
   *   Offset and position settings for the item being positioned.
   * @param {object}  referenceItemSettings
   *   Offset and position settings for the reference item.
   * @param {object} positionCss
   *   An object of CSS styles that will be applied to the positioned item.
   *
   * @return {{topComp: number, positionCss: object}}
   *   Object with top compensation and position css.
   */
  const calculateHorizontalFixedPositioning = (
    itemBeingPositioned,
    positionedItemSettings,
    referenceItemSettings,
    positionCss,
  ) => {
    // Compensation that will be applied to left positioning.
    let leftComp = 0;

    // Compensation is only needed if the reference and positioned item's
    // horizontal position do not match.
    if (
      referenceItemSettings.horizontal !== positionedItemSettings.horizontal
    ) {
      leftComp =
        positionedItemSettings.horizontal === 'left'
          ? -itemBeingPositioned.outerWidth() / 2
          : itemBeingPositioned.outerWidth() / 2;
      if (
        positionedItemSettings.horizontal !== 'center' &&
        referenceItemSettings.horizontal !== 'center'
      ) {
        // If neither item is centered, then compensate the full width of the
        // item being positioned.
        leftComp = itemBeingPositioned.outerWidth();
      }
    }

    const totalHorizontalOffsets =
      referenceItemSettings.horizontalOffset +
      positionedItemSettings.horizontalOffset;

    // Apply offsets.
    if (referenceItemSettings.horizontal === 'center') {
      const leftAmount =
        $(window).outerWidth() / 2 -
        itemBeingPositioned.outerWidth() / 2 -
        leftComp +
        totalHorizontalOffsets;
      positionCss.left = `${leftAmount}px`;
      positionCss.right = 'auto';
    } else if (referenceItemSettings.horizontal === 'right') {
      positionCss.right = `${0 - totalHorizontalOffsets - leftComp}px`;
      positionCss.left = 'auto';
    } else if (referenceItemSettings.horizontal === 'left') {
      positionCss.left = `${totalHorizontalOffsets - leftComp}px`;
      positionCss.right = 'auto';
    }

    return positionCss;
  };

  /**
   * Positions an item with CSS fixed positioning.
   *
   * @param {jQuery} itemBeingPositioned
   *   The element being positioned.
   * @param {object} positionedItemSettings
   *   Offset and position settings for the item being positioned.
   * @param {object}  referenceItemSettings
   *   Offset and position settings for the reference item.
   */
  const applyFixedPositioning = (
    itemBeingPositioned,
    positionedItemSettings,
    referenceItemSettings,
  ) => {
    let positionCss = calculateVerticalFixedPositioning(
      itemBeingPositioned,
      positionedItemSettings,
      referenceItemSettings,
      {
        position: 'fixed',
      },
    );
    positionCss = calculateHorizontalFixedPositioning(
      itemBeingPositioned,
      positionedItemSettings,
      referenceItemSettings,
      positionCss,
    );

    itemBeingPositioned.css(positionCss);
  };

  /**
   * Positions an item using Popper.
   *
   * @param {jQuery} itemBeingPositioned
   *   The element being positioned.
   * @param {Element} reference
   *   The element positioned against
   * @param {object} positionedItemSettings
   *   Offset and position settings for the item being positioned.
   * @param {object}  referenceItemSettings
   *   Offset and position settings for the reference item.
   * @param {object} options
   *   Options sent with the call to `.position()`.
   */
  const positionWithPopper = (
    itemBeingPositioned,
    reference,
    positionedItemSettings,
    referenceItemSettings,
    options,
  ) => {
    const modifiers = [];
    const opposites = {
      left: 'right',
      right: 'left',
      center: 'nope',
      top: 'bottom',
      bottom: 'top',
    };
    let placement;
    let primaryOffset = 0;
    let secondaryOffset = 0;
    let hAxis = false;

    // If the reference position is centered on both axes.
    if (
      referenceItemSettings.horizontal === 'center' &&
      referenceItemSettings.vertical === 'center'
    ) {
      placement = 'top';

      // Begin vertical offset by subtracting half the reference item's height.
      secondaryOffset -= Math.ceil($(reference).outerHeight() / 2);

      // Items positioned on their center axis need additional offset of half
      // their height.
      if (positionedItemSettings.vertical === 'center') {
        secondaryOffset -= Math.ceil(itemBeingPositioned.outerHeight() / 2);
      }

      // Items positioned on their top axis need additional offset of their full
      // height.
      if (positionedItemSettings.vertical === 'top') {
        secondaryOffset -= itemBeingPositioned.outerHeight();
      }

      // If the positioned item is not positioned on its center axis, additional
      // horizontal offset must be added.
      if (
        positionedItemSettings.horizontal === 'right' ||
        positionedItemSettings.horizontal === 'left'
      ) {
        const width = Math.ceil(itemBeingPositioned.outerWidth() / 2);
        primaryOffset +=
          positionedItemSettings.horizontal === 'left' ? width : -width;
      }
    } else if (referenceItemSettings.horizontal !== 'center') {
      // This condition is when the reference position is:
      // `{x: any, y: NOT-center}`

      // Indicates the the popper strategy uses the horizontal axis.
      hAxis = true;

      // Within this condition, the reference horizontal placement is right or
      // left. That value can become the Popper positioning strategy.
      placement = referenceItemSettings.horizontal;

      // If the reference vertical placement is top or bottom, the popper
      // positioning strategy needs  a '-start' or '-end' appended to it.
      if (
        referenceItemSettings.vertical === 'top' ||
        referenceItemSettings.vertical === 'bottom'
      ) {
        placement +=
          referenceItemSettings.vertical === 'top' ? '-start' : '-end';
      }

      if (referenceItemSettings.vertical !== positionedItemSettings.vertical) {
        const height = itemBeingPositioned.height() / 2;

        if (referenceItemSettings.vertical === 'center') {
          primaryOffset +=
            positionedItemSettings.vertical !== 'bottom' ? height : -height;
        } else if (
          referenceItemSettings.vertical === 'bottom' ||
          referenceItemSettings.vertical === 'top'
        ) {
          primaryOffset +=
            referenceItemSettings.vertical !== 'bottom' ? -height : height;

          // If the positioned and reference element vertical settings are
          // opposites, the vertical offset is the full height of the positioned
          // item.
          if (
            opposites[positionedItemSettings.vertical] ===
            referenceItemSettings.vertical
          ) {
            primaryOffset *= 2;
          }
        }
      }

      // If the reference and positioned item horizontal positions are not
      // opposites of each other, additional horizontal offsets are needed.
      if (
        referenceItemSettings.horizontal !==
        opposites[positionedItemSettings.horizontal]
      ) {
        // When the positioned item axis is centered, it is offset half of its
        // width, otherwise the offset is its full width.
        secondaryOffset -=
          positionedItemSettings.horizontal === 'center'
            ? itemBeingPositioned.width() / 2
            : itemBeingPositioned.width();
      }
    } else if (referenceItemSettings.vertical !== 'center') {
      // This condition is when the reference position is:
      // `{x: NOT-center, y: center}`

      // If the reference horizontal placement is center, and the vertical
      // is not, then the popper position will be 'top' or 'bottom';
      placement = referenceItemSettings.vertical;

      // If the positioned item axis is 'left' or 'right', and the reference
      // item position is not the opposite value, additional horizontal offset
      // is needed.
      if (
        referenceItemSettings.horizontal !==
          opposites[positionedItemSettings.horizontal] &&
        positionedItemSettings.horizontal !== 'center'
      ) {
        const width = itemBeingPositioned.outerWidth() / 2;
        primaryOffset +=
          positionedItemSettings.horizontal !== 'left' ? -width : width;
      }

      // If the reference item vertical position is not opposite value of the
      // positioned item axis, additional vertical offset is needed.
      if (
        referenceItemSettings.vertical !==
        opposites[positionedItemSettings.vertical]
      ) {
        secondaryOffset -=
          positionedItemSettings.vertical === 'center'
            ? itemBeingPositioned.outerHeight() / 2
            : itemBeingPositioned.outerHeight();
      }
    }

    // Consolidate offsets that were added via options.
    const verticalOffsets =
      referenceItemSettings.verticalOffset +
      positionedItemSettings.verticalOffset;
    const horizontalOffsets =
      referenceItemSettings.horizontalOffset +
      positionedItemSettings.horizontalOffset;

    // Include additional offsets configured via options. These are calculated
    // differently if the Popper position strategy is horizontal axis based.
    if (hAxis) {
      primaryOffset += verticalOffsets;
      secondaryOffset +=
        referenceItemSettings.horizontal === 'right'
          ? horizontalOffsets
          : -horizontalOffsets;
    } else {
      primaryOffset += horizontalOffsets;
      secondaryOffset +=
        placement === 'top' ? -verticalOffsets : verticalOffsets;
    }

    if (!placement) {
      placement = 'auto';
    }

    // If the options explicitly use one of the available 'none' collision
    // options, disable Popper's `flip` functionality, which automatically
    // repositions a Popper to keep it visible.
    if (
      options.hasOwnProperty('collision') &&
      options.collision.indexOf('none') !== -1
    ) {
      modifiers.push({
        name: 'flip',
        enabled: false,
      });
    }

    modifiers.push({
      name: 'offset',
      options: {
        offset: [primaryOffset, secondaryOffset],
      },
    });

    // If the item being positioned does not have an existing Popper instance.
    if (!itemBeingPositioned[0].hasAttribute('data-drupal-popper-instance')) {
      // If creating a new popper instance, add it to a global array keyed
      // by a unique id. This makes it possible to update existing Poppers.
      const uniqueId = Math.random().toString(36).substring(7) + Date.now();
      Drupal.PopperInstances[uniqueId] = Popper.createPopper(
        reference,
        itemBeingPositioned[0],
        {
          placement,
          modifiers,
        },
      );
    } else {
      // Update the existing Popper instance used by the element being
      // positioned.
      const uniqueId = itemBeingPositioned[0].getAttribute(
        'data-drupal-popper-instance',
      );
      Drupal.PopperInstances[uniqueId].setOptions({
        placement,
        modifiers,
      });
      Drupal.PopperInstances[uniqueId].update();
    }
  };

  $.fn.extend({
    position(options) {
      const itemBeingPositioned = this;
      let reference = {};

      /**
       * Parses a jQuery UI position config string for `at:` or `my:`.
       *
       * A position config string can contain both alignment and offset
       * configuration. This string is parsed and returned as an object that
       * separates horizontal and vertical alignment and their respective
       * offsets into distinct object properties.
       *
       * @param {string}offset
       *   Offset configuration in jQuery UI Position format.
       * @param {element} element
       *   The element being positioned.
       * @return {{horizontal: (*|string), verticalOffset: number, vertical: (*|string), horizontalOffset: number}}
       *   The horizontal and vertical alignment and offset values for the element.
       */
      const parseOffset = (offset, element) => {
        const rhorizontal = /left|center|right/;
        const rvertical = /top|center|bottom/;
        const roffset = /[+-]\d+(\.[\d]+)?%?/;
        const rposition = /^\w+/;
        const rpercent = /%$/;
        let positions = offset.split(' ');
        if (positions.length === 1) {
          if (rhorizontal.test(positions[0])) {
            positions.push('center');
          } else if (rvertical.test(positions[0])) {
            positions = ['center'].concat(positions);
          }
        }

        const horizontalOffset = roffset.exec(positions[0]);
        const verticalOffset = roffset.exec(positions[1]);
        positions = positions.map((pos) => rposition.exec(pos)[0]);

        return {
          horizontalOffset: horizontalOffset
            ? parseFloat(horizontalOffset[0]) *
              (rpercent.test(horizontalOffset[0])
                ? element.offsetWidth / 100
                : 1)
            : 0,
          verticalOffset: verticalOffset
            ? parseFloat(verticalOffset[0]) *
              (rpercent.test(verticalOffset[0]) ? element.offsetWidth / 100 : 1)
            : 0,
          horizontal: positions[0],
          vertical: positions[1],
        };
      };

      // The `of:` option defines the reference item. Check its type and convert
      // to and Element if needed.
      const { of } = options;
      if (typeof of === 'string') {
        reference = document.querySelector(of);
      } else if (of instanceof Element) {
        reference = of;
      } else if (of instanceof jQuery) {
        [reference] = of;
      } else if (of.toString() === '[object Window]') {
        reference = document.body;
      } else if (of instanceof Event) {
        // @todo determine if events need to be covered.
      }

      // Default settings for the item being positioned.
      let positionedItemSettings = {
        horizontalOffset: 0,
        verticalOffset: 0,
        horizontal: 'center',
        vertical: 'center',
      };

      // Default values for the reference item.
      let referenceItemSettings = {
        horizontalOffset: 0,
        verticalOffset: 0,
        horizontal: 'center',
        vertical: 'center',
      };

      // Update positioned item settings based on position() options.
      if (options.my) {
        positionedItemSettings = {
          ...positionedItemSettings,
          ...parseOffset(options.my, itemBeingPositioned[0]),
        };
      }

      // Update reference item settings based on position() options.
      if (options.at) {
        referenceItemSettings = {
          ...referenceItemSettings,
          ...parseOffset(options.at, reference),
        };
      }

      // When an item is positioned inside the document body use CSS positioning
      // instead of Popper.
      if (reference === document.body) {
        applyFixedPositioning(
          itemBeingPositioned,
          positionedItemSettings,
          referenceItemSettings,
        );
      } else {
        positionWithPopper(
          itemBeingPositioned,
          reference,
          positionedItemSettings,
          referenceItemSettings,
          options,
        );
      }
    },
  });
})(jQuery, Drupal, Popper);
