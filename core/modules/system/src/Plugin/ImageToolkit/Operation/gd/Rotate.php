<?php

namespace Drupal\system\Plugin\ImageToolkit\Operation\gd;

use Drupal\Component\Utility\Color;
use Drupal\Component\Utility\Rectangle;

/**
 * Defines GD2 rotate operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_rotate",
 *   toolkit = "gd",
 *   operation = "rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotates an image by the given number of degrees.")
 * )
 */
class Rotate extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'degrees' => [
        'description' => 'The number of (clockwise) degrees to rotate the image',
      ],
      'background' => [
        'description' => "A string specifying the hexadecimal color code to use as background for the uncovered area of the image after the rotation. E.g. '#000000' for black, '#ff00ff' for magenta, and '#ffffff' for white. For images that support transparency, this will default to transparent white",
        'required' => FALSE,
        'default' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Validate or set background color argument.
    if (!empty($arguments['background'])) {
      // Validate the background color: Color::hexToRgb does so for us.
      $background = Color::hexToRgb($arguments['background']) + ['alpha' => 0];
    }
    else {
      // Background color is not specified: use transparent white as background.
      $background = ['red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127];
    }
    // Store the color index for the background as that is what GD uses.
    $arguments['background_idx'] = imagecolorallocatealpha($this->getToolkit()->getResource(), $background['red'], $background['green'], $background['blue'], $background['alpha']);

    if ($this->getToolkit()->getType() === IMAGETYPE_GIF) {
      // GIF does not work with a transparency channel, but can define 1 color
      // in its palette to act as transparent.

      // Get the current transparent color, if any.
      $gif_transparent_id = imagecolortransparent($this->getToolkit()->getResource());
      if ($gif_transparent_id !== -1) {
        // The gif already has a transparent color set: remember it to set it on
        // the rotated image as well.
        $arguments['gif_transparent_color'] = imagecolorsforindex($this->getToolkit()->getResource(), $gif_transparent_id);

        if ($background['alpha'] >= 127) {
          // We want a transparent background: use the color already set to act
          // as transparent, as background.
          $arguments['background_idx'] = $gif_transparent_id;
        }
      }
      else {
        // The gif does not currently have a transparent color set.
        if ($background['alpha'] >= 127) {
          // But as the background is transparent, it should get one.
          $arguments['gif_transparent_color'] = $background;
        }
      }
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // PHP installations using non-bundled GD do not have imagerotate.
    if (!function_exists('imagerotate')) {
      $this->logger->notice('The image %file could not be rotated because the imagerotate() function is not available in this PHP installation.', ['%file' => $this->getToolkit()->getSource()]);
      return FALSE;
    }

    // In Drupal we rotate clockwise whereas GD rotates anti-clockwise. We need
    // to reconcile the value in Drupal with the argument to be passed to
    // rotate.
    $degrees = 360 - $arguments['degrees'];

    // Stores the original GD resource.
    $original_res = $this->getToolkit()->getResource();

    // Get expected width and height resulting from the rotation.
    $rotated_rect = (new Rectangle($this->getToolkit()->getWidth(), $this->getToolkit()->getHeight()))->rotate($degrees);
    $expected_width = $rotated_rect->getBoundingWidth();
    $expected_height = $rotated_rect->getBoundingHeight();

    // Rotate the image.
    if ($new_res = imagerotate($this->getToolkit()->getResource(), $degrees, $arguments['background_idx'])) {
      $this->getToolkit()->setResource($new_res);
      imagedestroy($original_res);

      // GIFs need to reassign the transparent color after performing the
      // rotate, but only do so, if the image already had transparency of its
      // own, or the rotate added a transparent background.
      if (!empty($arguments['gif_transparent_color'])) {
        $transparent_idx = imagecolorexactalpha($this->getToolkit()->getResource(), $arguments['gif_transparent_color']['red'], $arguments['gif_transparent_color']['green'], $arguments['gif_transparent_color']['blue'], $arguments['gif_transparent_color']['alpha']);
        imagecolortransparent($this->getToolkit()->getResource(), $transparent_idx);
      }

      // Resizes the image if width and height are not as expected.
      if ($this->getToolkit()->getWidth() != $expected_width || $this->getToolkit()->getHeight() != $expected_height) {
        // If either dimension of the current image is bigger than expected,
        // crop the image.
        if ($this->getToolkit()->getWidth() > $expected_width || $this->getToolkit()->getHeight() > $expected_height) {
          $crop_width = min($expected_width, $this->getToolkit()->getWidth());
          $crop_height = min($expected_height, $this->getToolkit()->getHeight());
          if (!$this->getToolkit()->apply('crop', [
            'x' => $this->getToolkit()->getWidth() / 2 - $crop_width / 2,
            'y' => $this->getToolkit()->getHeight() / 2 - $crop_height / 2,
            'width' => $crop_width,
            'height' => $crop_height,
          ])) {
            return FALSE;
          }
        }
        // If the image at this point is smaller than expected, place it above
        // a canvas of the expected dimensions.
        if ($this->getToolkit()->getWidth() < $expected_width || $this->getToolkit()->getHeight() < $expected_height) {
          // Store the current GD resource.
          $temp_res = $this->getToolkit()->getResource();

          // Prepare the canvas.
          $data = [
            'width' => $expected_width,
            'height' => $expected_height,
            'extension' => image_type_to_extension($this->getToolkit()->getType(), FALSE),
            'transparent_color' => $this->getToolkit()->getTransparentColor(),
            'is_temp' => TRUE,
          ];
          if (!$this->getToolkit()->apply('create_new', $data)) {
            return FALSE;
          }

          // Fill the canvas with the required background color.
          imagefill($this->getToolkit()->getResource(), 0, 0, $arguments['background_idx']);

          // Overlay the current image on the canvas.
          imagealphablending($temp_res, TRUE);
          imagesavealpha($temp_res, TRUE);
          imagealphablending($this->getToolkit()->getResource(), TRUE);
          imagesavealpha($this->getToolkit()->getResource(), TRUE);
          $x_pos = (int) ($expected_width / 2 - imagesx($temp_res) / 2);
          $y_pos = (int) ($expected_height / 2 - imagesy($temp_res) / 2);
          if (imagecopy($this->getToolkit()->getResource(), $temp_res, $x_pos, $y_pos, 0, 0, imagesx($temp_res), imagesy($temp_res))) {
            imagedestroy($temp_res);
          }
          else {
            // In case of failure, destroy the temporary resource and restore
            // the original one.
            imagedestroy($this->getToolkit()->getResource());
            $this->getToolkit()->setResource($temp_res);
            return FALSE;
          }
        }
      }
      return TRUE;
    }

    return FALSE;
  }

}
