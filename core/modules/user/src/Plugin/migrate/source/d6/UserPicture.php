<?php

namespace Drupal\user\Plugin\migrate\source\d6;

/**
 * Drupal 6 user picture source from database.
 *
 * @todo Support default picture?
 *
 * @MigrateSource(
 *   id = "d6_user_picture",
 *   source_module = "user"
 * )
 */
class UserPicture extends UserPictureFile {
}
