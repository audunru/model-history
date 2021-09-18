<?php

namespace audunru\ModelHistory\Tests\Unit;

use audunru\ModelHistory\Events\HistoryChanged;
use audunru\ModelHistory\Models\Change;
use audunru\ModelHistory\Tests\Models\Product;
use audunru\ModelHistory\Tests\Models\User;
use audunru\ModelHistory\Tests\TestCase;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function testItRecordsAProductChangeInHistory()
    {
        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'                => 'Old description',
            'purchased_at'               => '2021-01-14',
            'gross_cost'                 => 100,
            'tax_rate'                   => 0,
            'seller_name'                => 'Old seller name',
            'seller_address'             => 'Old seller address',
            'seller_phone'               => 'Old seller phone',
            'seller_identification'      => 'Old seller identification',
        ]);

        $product->update([
            'description'                => 'New description',
            'purchased_at'               => '2021-01-15',
            'gross_cost'                 => 200,
            'tax_rate'                   => 25,
            'seller_name'                => 'New seller name',
            'seller_address'             => 'New seller address',
            'seller_phone'               => 'New seller phone',
            'seller_identification'      => 'New seller identification',
        ]);

        $expectedChanges = [
            'original' => [
                'description'                => 'Old description',
                'purchased_at'               => '2021-01-14',
                'gross_cost'                 => 100,
                'tax_rate'                   => 0,
                'seller_name'                => 'Old seller name',
                'seller_address'             => 'Old seller address',
                'seller_phone'               => 'Old seller phone',
                'seller_identification'      => 'Old seller identification',
            ],
            'updated' => [
                'description'                => 'New description',
                'purchased_at'               => '2021-01-15',
                'gross_cost'                 => 200,
                'tax_rate'                   => 25,
                'seller_name'                => 'New seller name',
                'seller_address'             => 'New seller address',
                'seller_phone'               => 'New seller phone',
                'seller_identification'      => 'New seller identification',
            ],
        ];

        $this->assertEquals(1, $product->history->count());
        $this->assertEquals(1, $user->changes->count());

        $productChange = $product->history->first();
        $userChange = $user->changes->first();
        $this->assertEquals(1, $productChange->id);
        $this->assertEquals(1, $userChange->id);
        $this->assertEquals($product->id, $productChange->model->id);
        $this->assertEquals($product->id, $userChange->model->id);
        $this->assertEquals($user->id, $productChange->owner->id);
        $this->assertEquals($user->id, $userChange->owner->id);
        $this->assertEquals($expectedChanges, $productChange->changes);
        $this->assertEquals($expectedChanges, $userChange->changes);
    }

    public function testItRecordsAllChangesToProduct()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->travelTo(Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC'));
        $product = Product::factory()->create([
            'description'    => 'Old description',
        ]);

        // Travel forward 1 second to ensure that updated_at also changes
        $this->travel(1)->seconds();

        $product->setIgnored([]);
        $product->update([
            'description'    => 'New description',
        ]);

        $expectedChanges = [
            'original' => [
                'description' => 'Old description',
                'updated_at'  => '2020-01-01T00:00:00.000000Z',
            ],
            'updated' => [
                'description' => 'New description',
                'updated_at'  => '2020-01-01T00:00:01.000000Z',
            ],
        ];

        $productChange = $product->history->first();
        $this->assertEquals($expectedChanges, $productChange->changes);
    }

    public function testItRecordsSoftDelete()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->travelTo(Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC'));
        $product = Product::factory()->create();
        $product = $product->fresh();
        $product->delete();

        $expectedChanges = [
            'original' => [
                'deleted_at'  => null,
            ],
            'updated' => [
                'deleted_at'  => '2020-01-01T00:00:00.000000Z',
            ],
        ];

        $productChange = $product->history->first();
        $this->assertEquals($expectedChanges, $productChange->changes);
    }

    public function testItRecordsProductRestore()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->travelTo(Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC'));
        $product = Product::factory()->create();

        $product = $product->fresh();
        $product->delete();
        // Travel forward 1 second to ensure that deleted_at also changes
        $this->travel(1)->seconds();
        $product->restore();

        $expectedChanges = [
            'original' => [
                'deleted_at'  => '2020-01-01T00:00:00.000000Z',
            ],
            'updated' => [
                'deleted_at'  => null,
            ],
        ];

        $productChange = $product->history->first();
        $this->assertEquals($expectedChanges, $productChange->changes);
    }

    public function testItDoesNotRecordChangeWhenUpdatingAndNoUserIsAuthenticatedButLogsAWarning()
    {
        Event::fake([
            HistoryChanged::class,
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Changes where made to model audunru\ModelHistory\Tests\Models\Product with ID 1, but no user was authenticated. Changes: {"original":{"description":"Old description"},"updated":{"description":"New description"}}');

        $product = Product::factory()->create([
            'description'    => 'Old description',
        ]);

        $product->update(['description' => 'New description']);

        Event::assertNotDispatched(HistoryChanged::class);
    }

    public function testItDoesNotRecordChangeWhenDeletingAndNoUserIsAuthenticatedButLogsAWarning()
    {
        Event::fake([
            HistoryChanged::class,
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Changes where made to model audunru\ModelHistory\Tests\Models\Product with ID 1, but no user was authenticated. Changes: {"original":{"deleted_at":null},"updated":{"deleted_at":"2020-01-01T00:00:00.000000Z"}}');

        $product = Product::factory()->create();

        $this->travelTo(Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC'));
        $product = $product->fresh();
        $product->delete();

        Event::assertNotDispatched(HistoryChanged::class);
    }

    public function testItDoesNotRecordChangeWhenNothingHasChanged()
    {
        Event::fake([
            HistoryChanged::class,
        ]);

        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'    => 'Same description',
        ]);

        $product->update(['description' => 'Same description']);

        Event::assertNotDispatched(HistoryChanged::class);
    }

    public function testItDoesNotRecordChangeWhenChangeShouldBeIgnored()
    {
        Event::fake([
            HistoryChanged::class,
        ]);

        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'gross_cost'     => 100,
        ]);

        $product->addIgnored('gross_cost');

        $product->update(['gross_cost' => 200]);

        Event::assertNotDispatched(HistoryChanged::class);
    }

    public function testItDoesNotRecordChangeWheMultipleChangesShouldBeIgnored()
    {
        Event::fake([
            HistoryChanged::class,
        ]);

        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'gross_cost'     => 100,
            'tax_rate'       => 0,
        ]);

        $product->addIgnored(['gross_cost', 'tax_rate']);

        $product->update(['gross_cost' => 200, 'tax_rate' => 25]);

        Event::assertNotDispatched(HistoryChanged::class);
    }

    public function testItChecksThatUnchangedProductHasHistorySetToFalse()
    {
        $product = Product::factory()->create();

        $this->assertFalse($product->has_history);
    }

    public function testItChecksThatChangedProductHasHistorySetToTrue()
    {
        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'                => 'Some description',
        ]);

        $product->update(['description' => 'Some other description']);

        $this->assertTrue($product->has_history);
    }

    public function testOnlyOwnerIsEagerLoadedByDefault()
    {
        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'                => 'Old description',
        ]);

        $product->update([
            'description'                => 'New description',
        ]);

        $change = Change::first();

        $this->assertTrue($change->relationLoaded('owner'));
        $this->assertFalse($change->relationLoaded('model'));
    }

    public function testEagerLoadingCanBeDisabled()
    {
        $originalOwner = config('model-history.eager_load_owner');
        $originalModel = config('model-history.eager_load_model');

        config(['model-history.eager_load_owner' => false]);
        config(['model-history.eager_load_model' => false]);

        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'                => 'Old description',
        ]);

        $product->update([
            'description'                => 'New description',
        ]);

        $change = Change::first();

        $this->assertFalse($change->relationLoaded('owner'));
        $this->assertFalse($change->relationLoaded('model'));

        config(['model-history.eager_load_owner' => $originalOwner]);
        config(['model-history.eager_load_model' => $originalModel]);
    }

    public function testEagerLoadingCanBeEnabled()
    {
        $originalOwner = config('model-history.eager_load_owner');
        $originalModel = config('model-history.eager_load_model');

        config(['model-history.eager_load_owner' => true]);
        config(['model-history.eager_load_model' => true]);

        $user = User::factory()->create();
        $this->be($user);
        $product = Product::factory()->create([
            'description'                => 'Old description',
        ]);

        $product->update([
            'description'                => 'New description',
        ]);

        $change = Change::first();

        $this->assertTrue($change->relationLoaded('owner'));
        $this->assertTrue($change->relationLoaded('model'));

        config(['model-history.eager_load_owner' => $originalOwner]);
        config(['model-history.eager_load_model' => $originalModel]);
    }

    public function testTableNameIsHistoryByDefault()
    {
        $change = new Change();

        $this->assertEquals('history', $change->getTable());
    }

    public function testTableNameCanBeChanged()
    {
        $originalTable = config('model-history.history_table_name');

        config(['model-history.history_table_name' => 'other-table']);

        $change = new Change();

        $this->assertEquals('other-table', $change->getTable());

        config(['model-history.history_table_name' => $originalTable]);
    }
}
