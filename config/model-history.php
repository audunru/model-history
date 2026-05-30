<?php

return [
    /*
     * Table where the "Change" model will be stored
     */
    'history_table_name' => 'history',
    /*
     * Eager load the change model's owner
     */
    'eager_load_owner' => true,
    /*
     * Eager load the change model's model
     */
    'eager_load_model' => false,
    /*
     * Date format used when returning the Change model as JSON
     */
    'date_format' => 'Y-m-d H:i:s',
    /*
     * Attribute names that are never recorded in history, regardless of each model's own $ignored
     * list. Override in your published config to add or remove entries.
     */
    'default_ignored' => [
        'password',
        'remember_token',
        'api_token',
    ],
];
