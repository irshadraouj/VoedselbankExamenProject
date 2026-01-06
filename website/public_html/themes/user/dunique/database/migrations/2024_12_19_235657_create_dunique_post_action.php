<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateDuniquePostAction extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee('Model')->make('Action', [
            'class' => 'Dunique',
            'method' => 'Post',
            'csrf_exempt' => true,
        ])->save();
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee('Model')->get('Action')
            ->filter('class', 'Dunique')
            ->filter('method', 'Post')
            ->delete();
    }
}
