<?php

namespace audunru\ModelHistory\Tests\Unit;

use audunru\ModelHistory\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_makes_owner_columns_nullable_on_an_existing_table()
    {
        Schema::drop('history');
        Schema::create('history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('changes');
            $table->integer('model_id')->unsigned();
            $table->string('model_type');
            $table->integer('owner_id')->unsigned();
            $table->string('owner_type');
            $table->timestamps();
        });
        $this->assertFalse($this->isNullable('owner_id'));
        $this->assertFalse($this->isNullable('owner_type'));

        $this->makeOwnerNullableMigration()->up();

        $this->assertTrue($this->isNullable('owner_id'));
        $this->assertTrue($this->isNullable('owner_type'));
    }

    public function test_it_runs_on_a_table_where_owner_columns_are_already_nullable()
    {
        $this->assertTrue($this->isNullable('owner_id'));
        $this->assertTrue($this->isNullable('owner_type'));

        $this->makeOwnerNullableMigration()->up();

        $this->assertTrue($this->isNullable('owner_id'));
        $this->assertTrue($this->isNullable('owner_type'));
    }

    public function test_it_makes_owner_columns_not_nullable_when_reversed()
    {
        $this->makeOwnerNullableMigration()->down();

        $this->assertFalse($this->isNullable('owner_id'));
        $this->assertFalse($this->isNullable('owner_type'));
    }

    private function makeOwnerNullableMigration(): object
    {
        return include __DIR__.'/../../database/migrations/make_owner_nullable_in_history_table.php.stub';
    }

    private function isNullable(string $column): bool
    {
        return collect(Schema::getColumns('history'))->firstWhere('name', $column)['nullable'];
    }
}
