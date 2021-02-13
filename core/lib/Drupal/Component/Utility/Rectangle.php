<?php

namespace Drupal\Component\Utility;

/**
 * Rectangle rotation algebra class.
 *
 * This class is used by the image system to abstract, from toolkit
 * implementations, the calculation of the expected dimensions resulting from
 * an image rotate operation.
 *
 * Different versions of the libgd library embedded in PHP, and alternative
 * toolkits, use different algorithms to perform the rotation of an image and
 * result in different dimensions of the output image. This prevents
 * predictability of the final image size for instance by the image rotate
 * effect, or by image toolkit rotate operations.
 *
 * This class implements a calculation algorithm that returns, given input
 * width, height and rotation angle, dimensions of the expected image after
 * rotation that are consistent with those produced by the GD rotate image
 * toolkit operation using libgd 2.2.2 and above.
 *
 * @see \Drupal\system\Plugin\ImageToolkit\Operation\gd\Rotate
 */
class Rectangle {

  /**
   * The width of the rectangle.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the rectangle.
   *
   * @var int
   */
  protected $height;

  /**
   * The width of the rotated rectangle.
   *
   * @var int
   */
  protected $boundingWidth;

  /**
   * The height of the rotated rectangle.
   *
   * @var int
   */
  protected $boundingHeight;

  /**
   * Constructs a new Rectangle object.
   *
   * @param int $width
   *   The width of the rectangle.
   * @param int $height
   *   The height of the rectangle.
   */
  public function __construct($width, $height) {
    if ($width > 0 && $height > 0) {
      $this->width = $width;
      $this->height = $height;
      $this->boundingWidth = $width;
      $this->boundingHeight = $height;
    }
    else {
      throw new \InvalidArgumentException("Invalid dimensions ({$width}x{$height}) specified for a Rectangle object");
    }
  }

  /**
   * Rotates the rectangle.
   *
   * @param float $angle
   *   Rotation angle.
   *
   * @return $this
   */
  public function rotate($angle) {
    if ((int) $angle == $angle && $angle % 90 == 0) {
      // For rotations that are multiple of 90 degrees, no trigonometry is
      // needed.
      if (abs($angle) % 180 == 0) {
        $this->boundingWidth = $this->width;
        $this->boundingHeight = $this->height;
      }
      else {
        $this->boundingWidth = $this->height;
        $this->boundingHeight = $this->width;
      }
    }
    else {
      $rotate_affine_transform = $this->gdAffineRotate($angle);
      $bounding_box = $this->gdTransformAffineBoundingBox($this->width, $this->height, $rotate_affine_transform);
      $this->boundingWidth = $bounding_box['width'];
      $this->boundingHeight = $bounding_box['height'];
    }
    return $this;
  }

  /**
   * Set up a rotation affine transform.
   *
   * @param float $angle
   *   Rotation angle.
   *
   * @return array
   *   The resulting affine transform.
   *
   * @see https://libgd.github.io/manuals/2.2.2/files/gd_matrix-c.html#gdAffineRotate
   */
  private function gdAffineRotate(float $angle): array {
    $rad = deg2rad($angle);
    $sin_t = sin($rad);
    $cos_t = cos($rad);
    return [$cos_t, $sin_t, -$sin_t, $cos_t, 0, 0];
  }

  /**
   * Applies an affine transformation to a point.
   *
   * @param array $src
   *   The source point.
   * @param array $affine
   *   The affine transform to apply.
   *
   * @return array
   *   The resulting point.
   *
   * @see https://libgd.github.io/manuals/2.2.2/files/gd_matrix-c.html#gdAffineApplyToPointF
   */
  private function gdAffineApplyToPointF(array $src, array $affine): array {
    return [
      'x' => $src['x'] * $affine[0] + $src['y'] * $affine[2] + $affine[4],
      'y' => $src['x'] * $affine[1] + $src['y'] * $affine[3] + $affine[5],
    ];
  }

  /**
   * Returns the bounding box of an affine transform applied to a rectangle.
   *
   * @param int $width
   *   The width of the rectangle.
   * @param int $height
   *   The height of the rectangle.
   * @param array $affine
   *   The affine transform to apply.
   *
   * @return array
   *   The resulting bounding box.
   *
   * @see https://libgd.github.io/manuals/2.1.1/files/gd_interpolation-c.html#gdTransformAffineBoundingBox
   */
  private function gdTransformAffineBoundingBox(int $width, int $height, array $affine): array {
    $extent = [];
    $extent[0]['x'] = 0.0;
    $extent[0]['y'] = 0.0;
    $extent[1]['x'] = $width;
    $extent[1]['y'] = 0.0;
    $extent[2]['x'] = $width;
    $extent[2]['y'] = $height;
    $extent[3]['x'] = 0.0;
    $extent[3]['y'] = $height;

    for ($i = 0; $i < 4; $i++) {
      $extent[$i] = $this->gdAffineApplyToPointF($extent[$i], $affine);
    }
    $min = $extent[0];
    $max = $extent[0];

    for ($i = 1; $i < 4; $i++) {
      $min['x'] = $min['x'] > $extent[$i]['x'] ? $extent[$i]['x'] : $min['x'];
      $min['y'] = $min['y'] > $extent[$i]['y'] ? $extent[$i]['y'] : $min['y'];
      $max['x'] = $max['x'] < $extent[$i]['x'] ? $extent[$i]['x'] : $max['x'];
      $max['y'] = $max['y'] < $extent[$i]['y'] ? $extent[$i]['y'] : $max['y'];
    }

    return [
      'x' => (int) $min['x'],
      'y' => (int) $min['y'],
      'width' => (int) ceil(($max['x'] - $min['x'])) + 1,
      'height' => (int) ceil($max['y'] - $min['y']) + 1,
    ];
  }

  /**
   * Performs an imprecision check on the input value and fixes it if needed.
   *
   * GD that uses C floats internally, whereas we at PHP level use C doubles.
   * In some cases, we need to compensate imprecision.
   *
   * @param float $input
   *   The input value.
   * @param float $imprecision
   *   The imprecision factor.
   *
   * @return float
   *   A value, where imprecision is added to input if the delta part of the
   *   input is lower than the absolute imprecision.
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3198325
   */
  protected function fixImprecision($input, $imprecision) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/1', E_USER_DEPRECATED);
    if ($this->delta($input) < abs($imprecision)) {
      return $input + $imprecision;
    }
    return $input;
  }

  /**
   * Returns the fractional part of a float number, unsigned.
   *
   * @param float $input
   *   The input value.
   *
   * @return float
   *   The fractional part of the input number, unsigned.
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3198325
   */
  protected function fraction($input) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/1', E_USER_DEPRECATED);
    return abs((int) $input - $input);
  }

  /**
   * Returns the difference of a fraction from the closest between 0 and 1.
   *
   * @param float $input
   *   The input value.
   *
   * @return float
   *   the difference of a fraction from the closest between 0 and 1.
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3198325
   */
  protected function delta($input) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/1', E_USER_DEPRECATED);
    $fraction = $this->fraction($input);
    return $fraction > 0.5 ? (1 - $fraction) : $fraction;
  }

  /**
   * Gets the bounding width of the rectangle.
   *
   * @return int
   *   The bounding width of the rotated rectangle.
   */
  public function getBoundingWidth() {
    return $this->boundingWidth;
  }

  /**
   * Gets the bounding height of the rectangle.
   *
   * @return int
   *   The bounding height of the rotated rectangle.
   */
  public function getBoundingHeight() {
    return $this->boundingHeight;
  }

}
