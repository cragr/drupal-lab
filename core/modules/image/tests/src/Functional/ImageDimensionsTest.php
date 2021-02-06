<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that images have correct dimensions when styled.
 *
 * @group image
 */
class ImageDimensionsTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image', 'image_module_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected $profile = 'testing';

  /**
   * Test styled image dimensions cumulatively.
   *
   * @group legacy
   */
  public function testLegacyImageDimensions() {
    $this->expectDeprecation('The Drupal\image\Entity\ImageStyle::buildUrl method is deprecated since version 9.x.x and will be removed in y.y.y.');

    $image_factory = $this->container->get('image.factory');
    // Create a working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $original_uri = $file_system->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);

    // Create a style.
    /** @var $style \Drupal\image\ImageStyleInterface */
    $style = ImageStyle::create(['name' => 'test', 'label' => 'Test']);
    $style->save();
    $generated_uri = 'public://styles/test/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($style->buildUrl($original_uri));

    $variables = [
      '#theme' => 'image_style',
      '#style_name' => 'test',
      '#uri' => $original_uri,
      '#width' => 40,
      '#height' => 20,
    ];
    // Verify that the original image matches the hard-coded values.
    $image_file = $image_factory->get($original_uri);
    $this->assertEqual($variables['#width'], $image_file->getWidth());
    $this->assertEqual($variables['#height'], $image_file->getHeight());

    // Scale an image that is wider than it is high.
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ],
      'weight' => 0,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="120" height="60" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(120, $image_file->getWidth());
    $this->assertEqual(60, $image_file->getHeight());

    // Rotate 90 degrees anticlockwise.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => -90,
        'random' => FALSE,
      ],
      'weight' => 1,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="60" height="120" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(60, $image_file->getWidth());
    $this->assertEqual(120, $image_file->getHeight());

    // Scale an image that is higher than it is wide (rotated by previous effect).
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ],
      'weight' => 2,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(45, $image_file->getWidth());
    $this->assertEqual(90, $image_file->getHeight());

    // Test upscale disabled.
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 400,
        'height' => 200,
        'upscale' => FALSE,
      ],
      'weight' => 3,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(45, $image_file->getWidth());
    $this->assertEqual(90, $image_file->getHeight());

    // Add a desaturate effect.
    $effect = [
      'id' => 'image_desaturate',
      'data' => [],
      'weight' => 4,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(45, $image_file->getWidth());
    $this->assertEqual(90, $image_file->getHeight());

    // Add a random rotate effect.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => 180,
        'random' => TRUE,
      ],
      'weight' => 5,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" alt="" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);

    // Add a crop effect.
    $effect = [
      'id' => 'image_crop',
      'data' => [
        'width' => 30,
        'height' => 30,
        'anchor' => 'center-center',
      ],
      'weight' => 6,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="30" height="30" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(30, $image_file->getWidth());
    $this->assertEqual(30, $image_file->getHeight());

    // Rotate to a non-multiple of 90 degrees.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => 57,
        'random' => FALSE,
      ],
      'weight' => 7,
    ];

    $effect_id = $style->addImageEffect($effect);
    $style->save();
    // @todo Uncomment this once
    //   https://www.drupal.org/project/drupal/issues/2670966 is resolved.
    // $this->assertEqual('<img src="' . $url . '" width="41" height="41" alt="" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    // @todo Uncomment this once
    //   https://www.drupal.org/project/drupal/issues/2670966 is resolved.
    // $this->assertEqual(41, $image_file->getWidth());
    // $this->assertEqual(41, $image_file->getHeight());

    $effect_plugin = $style->getEffect($effect_id);
    $style->deleteImageEffect($effect_plugin);

    // Ensure that an effect can unset dimensions.
    $effect = [
      'id' => 'image_module_test_null',
      'data' => [],
      'weight' => 8,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" alt="" class="image-style-test" />', $this->getImageTag($variables));

    // Test URI dependent image effect.
    $style = ImageStyle::create(['name' => 'test_uri', 'label' => 'Test URI']);
    $effect = [
      'id' => 'image_module_test_uri_dependent',
      'data' => [],
      'weight' => 0,
    ];
    $style->addImageEffect($effect);
    $style->save();
    $variables = [
      '#theme' => 'image_style',
      '#style_name' => 'test_uri',
      '#uri' => $original_uri,
      '#width' => 40,
      '#height' => 20,
    ];
    // PNG original image. Should be resized to 100x100.
    $generated_uri = 'public://styles/test_uri/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($style->buildUrl($original_uri));
    $this->assertEqual('<img src="' . $url . '" width="100" height="100" alt="" loading="lazy" class="image-style-test-uri" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(100, $image_file->getWidth());
    $this->assertEqual(100, $image_file->getHeight());
    // GIF original image. Should be resized to 50x50.
    $file = $files[1];
    $original_uri = $file_system->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);
    $generated_uri = 'public://styles/test_uri/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($style->buildUrl($original_uri));
    $variables['#uri'] = $original_uri;
    $this->assertEqual('<img src="' . $url . '" width="50" height="50" alt="" loading="lazy" class="image-style-test-uri" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri);
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual(50, $image_file->getWidth());
    $this->assertEqual(50, $image_file->getHeight());
  }

  /**
   * Test styled image dimensions cumulatively.
   */
  public function testImageDimensions() {
    $image_factory = $this->container->get('image.factory');
    // Create a working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $original_uri = $file_system->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);

    // Create a style.
    /** @var $style \Drupal\image\ImageStyleInterface */
    $style = ImageStyle::create(['name' => 'test', 'label' => 'Test']);
    $style->save();

    $pipeline = \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($style)
      ->setSourceImageUri($original_uri);

    $generated_uri = 'public://styles/test/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($pipeline->getDerivativeImageUrl()->toString());

    $variables = [
      '#theme' => 'image_style',
      '#style_name' => 'test',
      '#uri' => $original_uri,
      '#width' => 40,
      '#height' => 20,
    ];
    // Verify that the original image matches the hard-coded values.
    $image_file = $image_factory->get($original_uri);
    $this->assertSame($variables['#width'], $image_file->getWidth());
    $this->assertSame($variables['#height'], $image_file->getHeight());

    // Scale an image that is wider than it is high.
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ],
      'weight' => 0,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" width="120" height="60" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(120, $image_file->getWidth());
    $this->assertSame(60, $image_file->getHeight());

    // Rotate 90 degrees anticlockwise.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => -90,
        'random' => FALSE,
      ],
      'weight' => 1,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" width="60" height="120" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(60, $image_file->getWidth());
    $this->assertSame(120, $image_file->getHeight());

    // Scale an image that is higher than it is wide (rotated by previous effect).
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ],
      'weight' => 2,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(45, $image_file->getWidth());
    $this->assertSame(90, $image_file->getHeight());

    // Test upscale disabled.
    $effect = [
      'id' => 'image_scale',
      'data' => [
        'width' => 400,
        'height' => 200,
        'upscale' => FALSE,
      ],
      'weight' => 3,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(45, $image_file->getWidth());
    $this->assertSame(90, $image_file->getHeight());

    // Add a desaturate effect.
    $effect = [
      'id' => 'image_desaturate',
      'data' => [],
      'weight' => 4,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" width="45" height="90" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(45, $image_file->getWidth());
    $this->assertSame(90, $image_file->getHeight());

    // Add a random rotate effect.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => 180,
        'random' => TRUE,
      ],
      'weight' => 5,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" alt="" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');

    // Add a crop effect.
    $effect = [
      'id' => 'image_crop',
      'data' => [
        'width' => 30,
        'height' => 30,
        'anchor' => 'center-center',
      ],
      'weight' => 6,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual('<img src="' . $url . '" width="30" height="30" alt="" loading="lazy" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(30, $image_file->getWidth());
    $this->assertSame(30, $image_file->getHeight());

    // Rotate to a non-multiple of 90 degrees.
    $effect = [
      'id' => 'image_rotate',
      'data' => [
        'degrees' => 57,
        'random' => FALSE,
      ],
      'weight' => 7,
    ];

    $effect_id = $style->addImageEffect($effect);
    $style->save();
    // @todo Uncomment this once
    //   https://www.drupal.org/project/drupal/issues/2670966 is resolved.
    // $this->assertSame('<img src="' . $url . '" width="41" height="41" alt="" class="image-style-test" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    // @todo Uncomment this once
    //   https://www.drupal.org/project/drupal/issues/2670966 is resolved.
    // $this->assertSame(41, $image_file->getWidth());
    // $this->assertSame(41, $image_file->getHeight());

    $effect_plugin = $style->getEffect($effect_id);
    $style->deleteImageEffect($effect_plugin);

    // Ensure that an effect can unset dimensions.
    $effect = [
      'id' => 'image_module_test_null',
      'data' => [],
      'weight' => 8,
    ];

    $style->addImageEffect($effect);
    $style->save();
    $this->assertSame('<img src="' . $url . '" alt="" class="image-style-test" />', $this->getImageTag($variables));

    // Test URI dependent image effect.
    $style = ImageStyle::create(['name' => 'test_uri', 'label' => 'Test URI']);
    $effect = [
      'id' => 'image_module_test_uri_dependent',
      'data' => [],
      'weight' => 0,
    ];
    $style->addImageEffect($effect);
    $style->save();
    $variables = [
      '#theme' => 'image_style',
      '#style_name' => 'test_uri',
      '#uri' => $original_uri,
      '#width' => 40,
      '#height' => 20,
    ];
    // PNG original image. Should be resized to 100x100.
    $pipeline = \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($style)
      ->setSourceImageUri($original_uri);
    $generated_uri = 'public://styles/test_uri/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($pipeline->getDerivativeImageUrl()->toString());

    $this->assertSame('<img src="' . $url . '" width="100" height="100" alt="" loading="lazy" class="image-style-test-uri" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(100, $image_file->getWidth());
    $this->assertSame(100, $image_file->getHeight());
    // GIF original image. Should be resized to 50x50.
    $file = $files[1];
    $original_uri = $file_system->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);
    $pipeline = \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($style)
      ->setSourceImageUri($original_uri);
    $generated_uri = 'public://styles/test_uri/public/' . $file_system->basename($original_uri);
    $url = file_url_transform_relative($pipeline->getDerivativeImageUrl()->toString());
    $variables['#uri'] = $original_uri;
    $this->assertSame('<img src="' . $url . '" width="50" height="50" alt="" loading="lazy" class="image-style-test-uri" />', $this->getImageTag($variables));
    $this->assertFileNotExists($generated_uri, 'Generated file does not exist.');
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri, 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertSame(50, $image_file->getWidth());
    $this->assertSame(50, $image_file->getHeight());
  }

  /**
   * Render an image style element.
   *
   * Function drupal_render() alters the passed $variables array by adding a new
   * key '#printed' => TRUE. This prevents next call to re-render the element.
   * We wrap drupal_render() in a helper protected method and pass each time a
   * fresh array so that $variables won't get altered and the element is
   * re-rendered each time.
   */
  protected function getImageTag($variables) {
    return str_replace("\n", NULL, \Drupal::service('renderer')->renderRoot($variables));
  }

}
