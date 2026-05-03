<?php

namespace Tests\Feature;

use App\Models\CodeCounter;
use App\Services\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private CodeGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CodeGeneratorService();
    }

    public function test_generate_returns_formatted_code(): void
    {
        $code = $this->service->generate('test_key', 'PREFIX-');

        $this->assertEquals('PREFIX-0001', $code);
    }

    public function test_generate_increments_counter_sequentially(): void
    {
        $first  = $this->service->generate('seq_key', 'X-');
        $second = $this->service->generate('seq_key', 'X-');
        $third  = $this->service->generate('seq_key', 'X-');

        $this->assertEquals('X-0001', $first);
        $this->assertEquals('X-0002', $second);
        $this->assertEquals('X-0003', $third);
    }

    public function test_generate_uses_custom_pad_length(): void
    {
        $code = $this->service->generate('pad_key', 'P-', 6);

        $this->assertEquals('P-000001', $code);
    }

    public function test_different_keys_are_independent(): void
    {
        $this->service->generate('key_a', 'A-');
        $this->service->generate('key_a', 'A-');
        $b = $this->service->generate('key_b', 'B-');

        $this->assertEquals('B-0001', $b);
    }

    public function test_generate_creates_code_counter_record(): void
    {
        $this->assertDatabaseMissing('code_counters', ['key' => 'new_key']);

        $this->service->generate('new_key', 'N-');

        $this->assertDatabaseHas('code_counters', ['key' => 'new_key', 'value' => 1]);
    }

    public function test_generate_category_code(): void
    {
        $code = $this->service->generateCategoryCode();

        $this->assertEquals('CAT-0001', $code);
    }

    public function test_generate_category_code_increments(): void
    {
        $this->service->generateCategoryCode();
        $second = $this->service->generateCategoryCode();

        $this->assertEquals('CAT-0002', $second);
    }

    public function test_generate_item_code_uses_category_kode(): void
    {
        $code = $this->service->generateItemCode('CAT-0001');

        $this->assertEquals('BRG-CAT-0001-0001', $code);
    }

    public function test_generate_item_codes_per_category_are_independent(): void
    {
        $this->service->generateItemCode('CAT-0001');
        $this->service->generateItemCode('CAT-0001');
        $code = $this->service->generateItemCode('CAT-0002');

        $this->assertEquals('BRG-CAT-0002-0001', $code);
    }

    public function test_generate_transaction_code_in(): void
    {
        $code = $this->service->generateTransactionCode('IN', '2026-05-03');

        $this->assertStringStartsWith('TRX-IN-20260503-', $code);
        $this->assertEquals('TRX-IN-20260503-0001', $code);
    }

    public function test_generate_transaction_code_out(): void
    {
        $code = $this->service->generateTransactionCode('OUT', '2026-05-03');

        $this->assertEquals('TRX-OUT-20260503-0001', $code);
    }

    public function test_generate_transaction_codes_per_type_are_independent(): void
    {
        $this->service->generateTransactionCode('IN', '2026-05-03');
        $this->service->generateTransactionCode('IN', '2026-05-03');
        $out = $this->service->generateTransactionCode('OUT', '2026-05-03');

        $this->assertEquals('TRX-OUT-20260503-0001', $out);
    }

    public function test_generate_transaction_codes_per_date_are_independent(): void
    {
        $this->service->generateTransactionCode('IN', '2026-05-03');
        $code = $this->service->generateTransactionCode('IN', '2026-05-04');

        $this->assertEquals('TRX-IN-20260504-0001', $code);
    }

    public function test_generate_multiple_codes_are_unique(): void
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->service->generate('unique_key', 'U-');
        }

        $this->assertCount(10, array_unique($codes));
    }
}
