<?php

namespace Drupal\update\SecurityAdvisories;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

/**
 * Provides a security advisory value object.
 *
 * These come from the PSA feed on Drupal.org.
 *
 * @internal
 *
 * @link https://www.drupal.org/docs/8/update/automatic-updates#s-public-service-announcement-psa-feed
 */
final class SecurityAdvisory {

  /**
   * The title of the advisory.
   *
   * @var string
   */
  protected $title;

  /**
   * The project name for the advisory.
   *
   * @var string
   */
  protected $project;

  /**
   * The project type for the advisory.
   *
   * @var string
   */
  protected $type;

  /**
   * Whether this advisory is a PSA instead of another type of advisory.
   *
   * @var bool
   */
  protected $isPsa;

  /**
   * The currently insecure versions of the project.
   *
   * @var string[]
   */
  protected $insecureVersions;

  /**
   * The URL to the advisory.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a SecurityAdvisories object.
   *
   * @param string $title
   *   The title of the advisory.
   * @param string $project
   *   The project name.
   * @param string $type
   *   The project type.
   * @param bool $is_psa
   *   Whether this advisory is a PSA.
   * @param string $url
   *   The URL to the advisory.
   * @param string[] $insecure_versions
   *   The versions of the project that are currently insecure. For public
   *   service announcements this list does not include versions that will be
   *   marked as insecure when the new security release is published.
   */
  private function __construct(string $title, string $project, string $type, bool $is_psa, string $url, array $insecure_versions) {
    $this->title = $title;
    $this->project = $project;
    $this->type = $type;
    $this->isPsa = $is_psa;
    $this->url = $url;
    $this->insecureVersions = $insecure_versions;
  }

  /**
   * Creates a SecurityAdvisories instance from an array.
   *
   * @param mixed[] $data
   *   The security advisory data as returned from the JSON feed.
   *
   * @return self
   *   A new SecurityAdvisories object.
   */
  public static function createFromArray(array $data): self {
    static::validateAdvisoryData($data);
    return new static(
      $data['title'],
      $data['project'],
      $data['type'],
      $data['is_psa'],
      $data['link'],
      $data['insecure']
    );
  }

  /**
   * Validates the security advisory data.
   *
   * @param mixed[] $data
   *   The advisory data.
   *
   * @throws \UnexpectedValueException
   *   Thrown if security advisory data is not valid.
   */
  protected static function validateAdvisoryData(array $data): void {
    $not_blank_constraints = [
      new Type(['type' => 'string']),
      new NotBlank(),
    ];
    $collection_constraint = new Collection([
      'fields' => [
        'title' => $not_blank_constraints,
        'project' => $not_blank_constraints,
        'type' => $not_blank_constraints,
        'link' => $not_blank_constraints,
        'is_psa' => new Choice(['choices' => [1, '1', 0, '0', TRUE, FALSE]]),
        'insecure' => new Type(['type' => 'array']),
      ],
      // Allow unknown fields, in the case that new fields are added to JSON
      // feed validation should still pass.
      'allowExtraFields' => TRUE,
    ]);
    $violations = Validation::createValidator()->validate($data, $collection_constraint);
    if ($violations->count()) {
      foreach ($violations as $violation) {
        $violation_messages[] = (string) $violation;
      }
      throw new \UnexpectedValueException('Malformed PSA data: ' . implode(",  \n", $violation_messages));
    }
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The project title.
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Gets the project associated with the advisory.
   *
   * @return string
   *   The project name.
   */
  public function getProject(): string {
    return $this->project;
  }

  /**
   * Gets the type of project associated with the advisory.
   *
   * @return string
   *   The project type.
   */
  public function getProjectType(): string {
    return $this->type;
  }

  /**
   * Whether the security advisory is a PSA or not.
   *
   * @return bool
   *   TRUE if the advisory is a PSA, otherwise FALSE.
   */
  public function isPsa(): bool {
    return $this->isPsa;
  }

  /**
   * Gets the currently insecure versions of the project.
   *
   * @return string[]
   *   The versions of the project that are currently insecure.
   */
  public function getInsecureVersions(): array {
    return $this->insecureVersions;
  }

  /**
   * Gets the URL to the security advisory.
   *
   * @return string
   *   The URL to the security advisory.
   */
  public function getUrl(): string {
    return $this->url;
  }

}
