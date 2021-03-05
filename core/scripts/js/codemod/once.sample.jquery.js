/* eslint-disable */

$('body')
  .once('detailsAria')
  .on('click.detailsAria', function () {});

const $collapsibleDetails = $(context)
  .find('details')
  .once('collapse')
  .addClass('collapse-processed');

const $progress = $('[data-drupal-progress]').once('batch');

$(context)
  .find('th.select-all')
  .closest('table')
  .once('table-select')
  .each(Drupal.tableSelect);

const $timezone = $(context).find('.timezone-detect').once('timezone');

$(window)
  .once('off-canvas')
  .on({});

$(this).removeOnce('big-pipe');

const $configurationForm = $(context)
  .find('.ckeditor-toolbar-configuration')
  .findOnce('ckeditor-configuration');

$('<div class="color-placeholder"></div>').once('color').prependTo(form);

$('.color-preview')
  .once('color')
  .append(`<div id="gradient-${i}"></div>`);

if ($('body').once('contextualToolbar-init').length) {
  initContextualToolbar(context);
}

$context
  .find('#filters-status-wrapper input.form-checkbox')
  .once('filter-editor-status')
  .each(function () {});

editors = $(context).find('[data-editor-for]').findOnce('editor');
editors = $(context).find('[data-editor-for]').removeOnce('editor');

$context
  .find(selector)
  .removeOnce('fileValidate')
  .off('change.fileValidate', Drupal.file.validateExtension);

$(context)
  .find('.js-filter-guidelines')
  .once('filter-guidelines')
  .find(':header')
  .hide()
  .closest('.js-filter-wrapper')
  .find('select.js-filter-list')
  .on('change.filterGuidelines', updateFilterGuidelines)
  // Need to trigger the namespaced event to avoid triggering formUpdated
  // when initializing the select.
  .trigger('change.filterGuidelines');

// Keep the jQuery find because of sizzle selector.
$(context)
  .find('table .bundle-settings .translatable :input')
  .once('translation-entity-admin-hide')
  .each(function () {});

// BAD
$('.js-click-to-select-trigger', context)
  .once('media-library-click-to-select')
  .on('click', (event) => {});

$(window)
  .once('media-library-selection-info')
  .on('dialog:aftercreate', () => {});

const $view = $(
  '.js-media-library-view[data-view-display-id="page"]',
  context,
).once('media-library-select-all');

$('.js-media-library-item-weight', context)
  .once('media-library-toggle')
  .parent()
  .hide();

$('body').removeOnce('copy-field-values').off('value:copy');
$(`#${ids.join(', #')}`)
  .removeOnce('copy-field-values')
  .off('blur');

this.$el
  .find(`#toolbar-link-${id}`)
  .once('toolbar-subtrees')
  .after(subtrees[id]);

initTableDrag($(context).find(`#${base}`).once('tabledrag'), base);

$('table')
  .findOnce('tabledrag')
  .trigger('columnschange', !!displayWeight);

// Replace
const $forms = (contextIsForm ? $context : $context.find('form')).once('form-updated');

const $source = $context
  .find(sourceId)
  .addClass('machine-name-source')
  .once('machine-name');


// Don't replace.
_.once(() => {});
CKEDITOR.once('instanceReady', (e) => {});
