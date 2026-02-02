<?php

declare(strict_types=1);

namespace Addresses\Tests\Helpers;

use Addresses\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;

/**
 * A dummy model for testing the HasAddresses trait.
 */
class TestModel extends Model
{
    use HasAddresses;

    protected $table = 'test_models';

    protected $guarded = [];
}
