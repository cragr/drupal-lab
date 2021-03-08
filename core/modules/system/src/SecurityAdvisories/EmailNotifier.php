<?php

namespace Drupal\update\SecurityAdvisories;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides a service to send email notifications for security advisories.
 *
 * @internal
 *   hook_mail_alter() can be used to alter the emails produced by this class.
 *   To send other emails or other notifications for service advisories use the
 *   'update.sa_fetcher' service directly to retrieve the advisories.
 */
final class EmailNotifier {

  use StringTranslationTrait;

  /**
   * State key used to store a hash of the last links emailed.
   */
  protected const LAST_LINKS_STATE_KEY = 'update_sa.last_links_string';

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The security advisory fetcher service.
   *
   * @var \Drupal\update\SecurityAdvisories\SecurityAdvisoriesFetcher
   */
  protected $securityAdvisoriesFetcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EmailNotifier object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\update\SecurityAdvisories\SecurityAdvisoriesFetcher $sa_fetcher
   *   The security advisory fetcher service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MailManagerInterface $mail_manager, SecurityAdvisoriesFetcher $sa_fetcher, ConfigFactoryInterface $config_factory, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->mailManager = $mail_manager;
    $this->securityAdvisoriesFetcher = $sa_fetcher;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->setStringTranslation($string_translation);
  }

  /**
   * Sends notifications when security advisories are available.
   *
   * @throws \GuzzleHttp\Exception\TransferException
   *   Thrown if an error occurs while retrieving security advisories.
   */
  public function send(): void {
    $notify_emails = $this->configFactory->get('update.settings')->get('notification.emails');
    if (!$notify_emails) {
      return;
    }
    $advisories = $this->securityAdvisoriesFetcher->getSecurityAdvisories();

    if (!$advisories) {
      return;
    }

    $advisories_hash = hash('sha256', serialize($advisories));
    // Return if the links are the same as the last links sent.
    if ($advisories_hash === $this->state->get(static::LAST_LINKS_STATE_KEY)) {
      return;
    }

    $params['subject'] = $this->formatPlural(
      count($advisories),
      'An urgent security announcement requires your attention for @site_name',
      '@count urgent security announcements require your attention for @site_name',
      ['@site_name' => $this->configFactory->get('system.site')->get('name')]
    );
    $advisory_links = array_map(function (SecurityAdvisory $advisory) {
      return new Link($advisory->getTitle(), Url::fromUri($advisory->getUrl()));
    }, $advisories);
    $params['body'] = [
      '#theme' => 'update_advisory_notification',
      '#advisories' => $advisory_links,
    ];
    $user_storage = $this->entityTypeManager->getStorage('user');
    foreach ($user_storage->loadByProperties(['mail' => $notify_emails]) as $user) {
      $params['langcode'] = $user->getPreferredLangcode();
      $this->mailManager->mail('update', 'advisory_notify', $user->mail, $params['langcode'], $params);
    }
    $this->state->set(static::LAST_LINKS_STATE_KEY, $advisories_hash);
  }

}
