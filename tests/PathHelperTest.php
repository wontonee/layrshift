<?php
/**
 * PathHelper tests.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Tests;

use LayrShift\PathHelper;
use PHPUnit\Framework\TestCase;

final class PathHelperTest extends TestCase {

	public function test_is_php_file(): void {
		$this->assertTrue( PathHelper::is_php_file( '/tmp/example.php' ) );
		$this->assertFalse( PathHelper::is_php_file( '/tmp/example.txt' ) );
	}
}
