<?php

namespace Tests\Unit;

use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NumberSequenceService $sequenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequenceService = new NumberSequenceService();
    }

    public function test_it_generates_sequential_customer_codes_starting_from_one(): void
    {
        $code1 = $this->sequenceService->generateCustomerCode();
        $code2 = $this->sequenceService->generateCustomerCode();
        $code3 = $this->sequenceService->generateCustomerCode();

        $this->assertSame('CUST-0001', $code1);
        $this->assertSame('CUST-0002', $code2);
        $this->assertSame('CUST-0003', $code3);

        $this->assertDatabaseHas('number_sequences', [
            'sequence_key' => 'customer',
            'period' => 'all',
            'last_value' => 3,
        ]);
    }

    public function test_it_generates_order_number_with_period_and_prefix(): void
    {
        $date = now();
        $expectedPeriod = $date->format('Ymd');

        $orderNum1 = $this->sequenceService->generateOrderNumber('LD', $date);
        $orderNum2 = $this->sequenceService->generateOrderNumber('LD', $date);

        $this->assertSame("LD-{$expectedPeriod}-0001", $orderNum1);
        $this->assertSame("LD-{$expectedPeriod}-0002", $orderNum2);

        $this->assertDatabaseHas('number_sequences', [
            'sequence_key' => 'order',
            'period' => $expectedPeriod,
            'last_value' => 2,
        ]);
    }

    public function test_it_expands_padding_gracefully_beyond_four_digits(): void
    {
        NumberSequence::create([
            'sequence_key' => 'customer',
            'period' => 'all',
            'last_value' => 9999,
        ]);

        $code = $this->sequenceService->generateCustomerCode();

        $this->assertSame('CUST-10000', $code);
    }
}
