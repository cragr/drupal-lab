<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Response subscriber to handle finished responses for the anonymous user.
 */
class AnonymousUserResponseSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an AnonymousUserResponseSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * Adds a cache tag if the 'user.permissions' cache context is present.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // The 'user.permissions' cache context ensures that if the permissions for
    // a role are modified, users are not served stale render cache content.
    // But, when entire responses are cached in reverse proxies, the value for
    // the cache context is never calculated, causing the stale response to not
    // be invalidated. Therefore, when varying by permissions and the current
    // user is the anonymous user, also add the cache tag for the 'anonymous'
    // role.
    if (in_array('user.permissions', $response->getCacheableMetadata()->getCacheContexts())) {
      $per_permissions_response_for_anon = new CacheableMetadata();
      $per_permissions_response_for_anon->setCacheTags(['config:user.role.anonymous']);
      $response->addCacheableDependency($per_permissions_response_for_anon);
    }
  }

  /**
   * Check if the user has cookies enabled.
   *
   * If the user was redirected to this page on an attempted login but the
   * login didn't succeed, warn them about missing cookies.
   *
   * @see \Drupal\user\Form\UserLoginForm::submitForm()
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelCheckUserCookies(RequestEvent $event) {
    $request = $event->getRequest();
    if ($request->query->get('state') === 'loggedin' && $this->currentUser->isAnonymous()) {
      $domain = ini_get('session.cookie_domain') ? ltrim(ini_get('session.cookie_domain'), '.') : $request->server->get('HTTP_HOST');
      $this->messenger->addMessage($this->t('To log in to this site, your browser must accept cookies from the domain %domain.', ['%domain' => $domain]), 'error');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    // Priority 5, so that it runs before FinishResponseSubscriber, but after
    // event subscribers that add the associated cacheability metadata (which
    // have priority 10). This one is conditional, so must run after those.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 5];
    $events[KernelEvents::REQUEST][] = ['onKernelCheckUserCookies'];
    return $events;
  }

}
