<?php

namespace audunru\ModelHistory\Tests\Feature;

use audunru\ModelHistory\Tests\Models\Product;
use audunru\ModelHistory\Tests\Models\User;
use audunru\ModelHistory\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2019-10-13 12:13:14'));

        $this->user = User::factory()->create();
        $this->be($this->user);
        $this->product = Product::factory()->create([
            'description'                => 'Old description',
            'purchased_at'               => '2021-01-14',
            'gross_cost'                 => 100,
            'tax_rate'                   => 0,
            'seller_name'                => 'Old seller name',
            'seller_address'             => 'Old seller address',
            'seller_phone'               => 'Old seller phone',
            'seller_identification'      => 'Old seller identification',
        ]);

        $this->product->update([
            'description'                => 'New description',
            'purchased_at'               => '2021-01-15',
            'gross_cost'                 => 200,
            'tax_rate'                   => 25,
            'seller_name'                => 'New seller name',
            'seller_address'             => 'New seller address',
            'seller_phone'               => 'New seller phone',
            'seller_identification'      => 'New seller identification',
        ]);
    }

    public function testItGetsHistory()
    {
        $response = $this->json('GET', '/history');

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [[
                'id',
                'changes',
                'owner',
                'created_at',
                'updated_at',
            ]]])
            ->assertExactJson(['data' => [[
                'id'      => 1,
                'changes' => [
                    'original' => [
                        'description'           => 'Old description',
                        'purchased_at'          => '2021-01-14',
                        'gross_cost'            => 100,
                        'tax_rate'              => 0,
                        'seller_name'           => 'Old seller name',
                        'seller_address'        => 'Old seller address',
                        'seller_phone'          => 'Old seller phone',
                        'seller_identification' => 'Old seller identification',
                    ],
                    'updated' => [
                        'description'           => 'New description',
                        'purchased_at'          => '2021-01-15',
                        'gross_cost'            => 200,
                        'tax_rate'              => 25,
                        'seller_name'           => 'New seller name',
                        'seller_address'        => 'New seller address',
                        'seller_phone'          => 'New seller phone',
                        'seller_identification' => 'New seller identification',
                    ],
                ],
                'owner' => [
                    'id'    => $this->user->id,
                    'name'  => $this->user->name,
                    'email' => $this->user->email,
                ],
                'created_at' => '2019-10-13 12:13:14',
                'updated_at' => '2019-10-13 12:13:14',
            ]]]);
    }

    public function testItChangesTheDateFormat()
    {
        $originalDateFormat = config('model-history.date_format');

        config(['model-history.date_format' => 'd.m.Y']);

        $response = $this->json('GET', '/history');

        $response
        ->assertStatus(200)
        ->assertJson(['data' => [[
            'created_at' => '13.10.2019',
            'updated_at' => '13.10.2019',
        ]]]);

        config(['model-history.date_format' => $originalDateFormat]);
    }
}
