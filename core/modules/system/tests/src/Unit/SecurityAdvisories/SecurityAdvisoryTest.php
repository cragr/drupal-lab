<?php

namespace Drupal\Tests\system\Unit\SecurityAdvisories;

use Drupal\Tests\UnitTestCase;
use Drupal\system\SecurityAdvisories\SecurityAdvisory;

/**
 * @coversDefaultClass \Drupal\system\SecurityAdvisories\SecurityAdvisory
 *
 * @group system
 */
class SecurityAdvisoryTest extends UnitTestCase {

  /**
   * Tests creating with valid data.
   *
   * @covers ::createFromArray
   */
  public function testCreateFromArray(): void {
    $data = $this->getValidData();
    $sa = SecurityAdvisory::createFromArray($data);
    $this->assertInstanceOf(SecurityAdvisory::class, $sa);
    $this->assertSame($data['title'], $sa->getTitle());
    $this->assertSame($data['project'], $sa->getProject());
    $this->assertSame($data['type'], $sa->getProjectType());
    $this->assertSame($data['link'], $sa->getUrl());
    $this->assertSame($data['insecure'], $sa->getInsecureVersions());
    $this->assertSame(FALSE, $sa->isPsa());
  }

  /**
   * Tests creating with possible values of 'is_psa'.
   *
   * @param mixed $value
   *   The 'is_psa' value to test.
   * @param bool $expected
   *   The expected value from ::isPsa().
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayIsPsa
   */
  public function testCreateFromArrayIsPsa($value, bool $expected): void {
    $data = $this->getValidData();
    $data['is_psa'] = $value;
    $this->assertSame($expected, SecurityAdvisory::createFromArray($data)->isPsa());
  }

  /**
   * Data provider for testCreateFromArrayIsPsa().
   */
  public function providerCreateFromArrayIsPsa(): array {
    return [
      [1, TRUE],
      ['1', TRUE],
      [TRUE, TRUE],
      [0, FALSE],
      ['0', FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Tests exceptions with missing fields.
   *
   * @param string $missing_field
   *   The field to test.
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayMissingField
   */
  public function testCreateFromArrayMissingField(string $missing_field): void {
    $data = $this->getValidData();
    unset($data[$missing_field]);
    $this->expectException(\UnexpectedValueException::class);
    $expected_message = 'Malformed PSA data:.*' . preg_quote("Array[$missing_field]:", '/');
    $expected_message .= '.*This field is missing';
    $this->expectExceptionMessageMatches("/$expected_message/s");
    SecurityAdvisory::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayMissingField().
   */
  public function providerCreateFromArrayMissingField(): array {
    return [
      'title' => ['title'],
      'link' => ['link'],
      'project' => ['project'],
      'type' => ['type'],
      'is_psa' => ['is_psa'],
      'insecure' => ['insecure'],
    ];
  }

  /**
   * Tests exceptions for invalid field types.
   *
   * @param string $invalid_field
   *   The field to test for an invalid value.
   * @param string $expected_type_message
   *   The expected message for the field.
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayInvalidField
   */
  public function testCreateFromArrayInvalidField(string $invalid_field, string $expected_type_message): void {
    $data = $this->getValidData();
    // Set the field a value that is not valid for any of the fields in the
    // feed.
    $data[$invalid_field] = new \stdClass();
    $this->expectException(\UnexpectedValueException::class);
    $expected_message = 'Malformed PSA data:.*' . preg_quote("Array[$invalid_field]:", '/');
    $expected_message .= ".*$expected_type_message";
    $this->expectExceptionMessageMatches("/$expected_message/s");
    SecurityAdvisory::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayInvalidField().
   */
  public function providerCreateFromArrayInvalidField(): array {
    return [
      'title' => ['title', 'This value should be of type string.'],
      'link' => ['link', 'This value should be of type string.'],
      'project' => ['project', 'This value should be of type string.'],
      'type' => ['type', 'This value should be of type string.'],
      'is_psa' => ['is_psa', 'The value you selected is not a valid choice.'],
      'insecure' => ['insecure', 'This value should be of type array.'],
    ];
  }

  /**
   * Gets valid data for a security advisory.
   *
   * @return mixed[]
   *   The data for the security advisory.
   */
  protected function getValidData(): array {
    return [
      'title' => 'Generic Module1 Test - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
      'link' => 'https://www.drupal.org/SA-CONTRIB-2019-02-02',
      'project' => 'generic_module1_test',
      'type' => 'module',
      'is_psa' => 0,
      'insecure' => [
        '8.x-1.1',
      ],
      'pubDate' => 'Tue, 19 Mar 2019 12 => 50 => 00 +0000',
      // New fields added to the JSON feed should be ignored and not cause a
      // validation error.
      'unknown_field' => 'ignored value',
    ];
  }

}
