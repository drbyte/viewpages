<?php
/**
 * Class CreateVppagesTable
 */

use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateVppagesTable
 *
 * Migration script that creates the vptemplates table.
 */
class CreateVppagesTable extends Migration
{
    /**@var string */
    protected $tableName;

    public function __construct()
    {
        $this->tableName = 'vppages';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var \Illuminate\Database\Schema\Blueprint $table */
        Schema::create($this->tableName, function ($table) {
            $table->increments('id');
            $table->integer('vptemplate_id')->unsigned();
            $table->string('key', 255)->default('')->index();
            $table->string('url', 255)->default('')->index();
            $table->string('name', 255)->default('');
            $table->string('description', 255)->nullable();
            $table->boolean('is_secure')->default(false);
            $table->boolean('is_homepage')->default(false);
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vptemplate_id')
                ->references('id')->on('vptemplates')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->tableName);
    }
}
