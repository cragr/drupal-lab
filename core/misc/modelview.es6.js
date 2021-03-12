/**
 * @file
 * Attaches behaviors for the Tour module's toolbar tab.
 */

((Drupal, drupalSettings) => {
  Drupal.listenTo = (modelEvent, callback) => {
    window.addEventListener(modelEvent, callback);
  };

  Drupal.modelSet = (modelEvent, settingName, data = {}) => {
    const originalSettings = window.drupalSettings[settingName] || {};
    window.drupalSettings[settingName] = { ...originalSettings, ...data };
    window.dispatchEvent(new Event(modelEvent));
  };
})(Drupal, drupalSettings);
