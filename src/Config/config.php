<?php
$laravelBefore8 = version_compare(app()->version(), "8.0.0") < 0;

return [
    /**
     * The default namespace and export path for the seeder.
     */
    "export_namespace" => $laravelBefore8 ? "Database\Seeds" : "Database\Seeders",
    "export_model_namespace" => "\Models",
    "export_table_namespace" => "\Tables",

    /**
     * The default prefix and suffix for the seeder.
     */
    "prefix" => "",
    "suffix" => "Seeder",

    /**
     * The connection to seed the data, left it into null to go default
     */
    "connection" => null,
];
