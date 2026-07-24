<?php

namespace Kuroragi\GeneralHelper\Tests\Unit;

use Kuroragi\GeneralHelper\Tests\TestCase;
use Kuroragi\GeneralHelper\Macros\EloquentMacros;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class MacrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a minimal test table
        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_posts');
        parent::tearDown();
    }

    public function test_blueprint_blameable_macro_is_registered(): void
    {
        $this->assertTrue(Blueprint::hasMacro('blameable'));
    }

    public function test_blueprint_created_by_macro_is_registered(): void
    {
        $this->assertTrue(Blueprint::hasMacro('createdBy'));
    }

    public function test_blueprint_updated_by_macro_is_registered(): void
    {
        $this->assertTrue(Blueprint::hasMacro('updatedBy'));
    }

    public function test_blueprint_deleted_by_macro_is_registered(): void
    {
        $this->assertTrue(Blueprint::hasMacro('deletedBy'));
    }

    public function test_blueprint_drop_blameable_macro_is_registered(): void
    {
        $this->assertTrue(Blueprint::hasMacro('dropBlameable'));
    }

    public function test_eloquent_created_by_macro_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Database\Eloquent\Builder::hasMacro('createdBy'));
    }

    public function test_eloquent_updated_by_macro_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Database\Eloquent\Builder::hasMacro('updatedBy'));
    }

    public function test_eloquent_deleted_by_macro_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Database\Eloquent\Builder::hasMacro('deletedBy'));
    }

    public function test_eloquent_created_by_scope_applies_where_clause(): void
    {
        $query = TestPost::createdBy(5)->toSql();
        $this->assertStringContainsString('created_by', $query);
    }
}

class TestPost extends Model
{
    protected $table = 'test_posts';
    protected $guarded = [];
}
