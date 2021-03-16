(($, Drupal, Modernizr) => {
  Drupal.contextual.ContextualModelView = class {
    constructor($contextual, $region, options) {
      this.title = options.title || '';
      this.regionIsHovered = false;
      this._hasFocus = false;
      this._isOpen = false;
      this._isLocked = false;
      this.strings = options.strings;
      this.timer = NaN;
      this.modelId = btoa(Math.random()).substring(0, 12);
      this.$region = $region;
      this.$contextual = $contextual;

      if (!Modernizr.touchevents) {
        $region.on({
          mouseenter: () => {
            this.regionIsHovered = true;
          },
          mouseleave: () => {
            this.regionIsHovered = false;
          },
          'mouseleave mouseenter': () => this.render(),
        });
        $contextual.on('mouseenter', () => {
          this.focus();
          this.render();
        });
      }

      this.$contextual.find('.trigger').on({
        click: () => {
          this.toggleOpen();
        },
        touchend: () => {
          this.touchEndToClick();
        },
        focus: () => {
          this.focus();
        },
        blur: () => {
          this.blur();
        },
        'click blur touchend focus': () => this.render(),
      });

      this.$contextual.find('.contextual-links a').on({
        click: () => {
          this.close();
          this.blur();
        },
        touchend: () => {
          this.touchEndToClick();
        },
        focus: () => {
          this.focus();
        },
        blur: () => {
          this.waitCloseThenBlur();
        },
        'click blur touchend focus': () => this.render(),
      });

      this.render();

      // Let other JavaScript react to the adding of a new contextual link.
      $(document).trigger('drupalContextualLinkAdded', {
        $el: $contextual,
        $region,
        model: this,
      });
    }

    render() {
      const { isOpen } = this;
      const isVisible = this.isLocked || this.regionIsHovered || isOpen;
      this.$region.toggleClass('focus', this.hasFocus);
      this.$contextual
        .toggleClass('open', isOpen)
        // Update the visibility of the trigger.
        .find('.trigger')
        .toggleClass('visually-hidden', !isVisible);

      this.$contextual.find('.contextual-links').prop('hidden', !isOpen);
      this.$contextual
        .find('.trigger')
        .text(
          Drupal.t('@action @title configuration options', {
            '@action': !isOpen ? this.strings.open : this.strings.close,
            '@title': this.title,
          }),
        )
        .attr('aria-pressed', isOpen);
    }

    // eslint-disable-next-line class-methods-use-this
    touchEndToClick(event) {
      event.preventDefault();
      event.target.click();
    }

    waitCloseThenBlur() {
      this.timer = window.setTimeout(() => {
        this.isOpen = false;
        this.hasFocus = false;
      }, 150);
    }

    toggleOpen() {
      const newIsOpen = !this.isOpen;
      this.isOpen = newIsOpen;
      if (newIsOpen) {
        this.focus();
      }
      return this;
    }

    focus() {
      const { modelId } = this;
      Drupal.contextual.instances.forEach((model) => {
        if (model.modelId !== modelId) {
          model.close().blur();
        }
      });
      window.clearTimeout(this.timer);
      this.hasFocus = true;
      return this;
    }

    blur() {
      if (!this.isOpen) {
        this.hasFocus = false;
      }
      return this;
    }

    close() {
      this.isOpen = false;
      return this;
    }

    get hasFocus() {
      return this._hasFocus;
    }

    set hasFocus(value) {
      this._hasFocus = value;
      this.render();
    }

    get isOpen() {
      return this._isOpen;
    }

    set isOpen(value) {
      this._isOpen = value;
      // Nested contextual region handling: hide any nested contextual triggers.
      this.$region
        .closest('.contextual-region')
        .find('.contextual .trigger:not(:first)')
        .toggle(!this.isOpen);
    }

    get isLocked() {
      return this._isLocked;
    }

    set isLocked(value) {
      if (value !== this._isLocked) {
        this._isLocked = value;
        this.render();
      }
    }
  };
})(jQuery, Drupal, Modernizr);
