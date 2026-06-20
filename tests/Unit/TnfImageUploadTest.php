<?php

namespace Tests\Unit;

use App\Support\TnfImageUpload;
use Tests\TestCase;

class TnfImageUploadTest extends TestCase
{
    public function test_default_max_size_is_150_kb(): void
    {
        config(['tnf.max_image_kb' => 150]);

        $this->assertSame(150, TnfImageUpload::maxKb());
        $this->assertSame(150 * 1024, TnfImageUpload::maxBytes());
    }

    public function test_validation_rules_include_max_kb(): void
    {
        config(['tnf.max_image_kb' => 150]);

        $this->assertContains('max:150', TnfImageUpload::validationRules());
    }
}
