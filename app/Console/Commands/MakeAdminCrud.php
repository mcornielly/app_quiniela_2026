<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeAdminCrud extends Command
{
    protected $signature = 'make:admin-crud {model}';

    protected $description = 'Generate Admin CRUD (Controller + Index + Form)';

    public function handle()
    {
        $model = $this->argument('model');

        $modelClass = Str::studly($model);
        $models = Str::pluralStudly($modelClass);

        $route = Str::plural(Str::snake($model));
        $routeSingular = Str::snake($model);

        $modelFull = "App\\Models\\{$modelClass}";

        if (!class_exists($modelFull)) {
            $this->error("Model {$modelClass} not found.");
            return;
        }

        $table = (new $modelFull)->getTable();

        if (!Schema::hasTable($table)) {
            $this->error("Table {$table} not found.");
            return;
        }

        $columns = Schema::getColumnListing($table);

        $columns = collect($columns)->reject(function ($col) {
            return in_array($col, ['id', 'created_at', 'updated_at']);
        });

        $this->generateController($modelClass, $models, $route, $routeSingular);
        $this->generateIndex($modelClass, $models, $route, $columns);
        $this->generateForm($modelClass, $routeSingular, $route, $columns);

        $this->info("Admin CRUD for {$modelClass} generated successfully.");
    }

    protected function generateController($model, $models, $route, $routeSingular)
    {
        $stub = File::get(resource_path('stubs/admin-controller.stub'));

        $stub = str_replace(
            ['{{model}}', '{{models}}', '{{route}}', '{{routeSingular}}'],
            [$model, $models, $route, $routeSingular],
            $stub
        );

        $path = app_path("Http/Controllers/Admin/{$model}Controller.php");

        if (File::exists($path)) {
            $this->warn("Controller already exists.");
            return;
        }

        File::put($path, $stub);
    }

    protected function generateIndex($model, $models, $route, $columns)
    {
        $stub = File::get(resource_path('stubs/admin-index.stub'));

        $tableColumns = '';

        foreach ($columns as $column) {

            $label = strtoupper(str_replace('_', ' ', $column));

            $tableColumns .= "{ key: '{$column}', label: '{$label}' },\n";
        }

        $stub = str_replace(
            ['{{model}}', '{{models}}', '{{route}}', '{{columns}}'],
            [$model, $models, $route, $tableColumns],
            $stub
        );

        $path = resource_path("js/Pages/Admin/{$models}/Index.vue");

        if (File::exists($path)) {
            $this->warn("Index already exists.");
            return;
        }

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $stub);
    }

    protected function generateForm($model, $routeSingular, $route, $columns)
    {
        $stub = File::get(resource_path('stubs/admin-form.stub'));

        $formFields = '';
        $inputs = '';

        foreach ($columns as $column) {

            $formFields .= "{$column}: props.{$routeSingular}?->{$column} || '',\n";

            $label = Str::title(str_replace('_', ' ', $column));

            if (Str::endsWith($column, '_id')) {

                $inputs .= "

<div>
<label class=\"block mb-2 text-sm font-medium text-gray-900 dark:text-white\">
{$label}
</label>

<select
v-model=\"form.{$column}\"
class=\"bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5
dark:bg-gray-700 dark:border-gray-600 dark:text-white\">

<option value=\"\">Select {$label}</option>

</select>
</div>

";

            } else {

                $inputs .= "

<div>
<label class=\"block mb-2 text-sm font-medium text-gray-900 dark:text-white\">
{$label}
</label>

<input
v-model=\"form.{$column}\"
type=\"text\"
class=\"bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5
dark:bg-gray-700 dark:border-gray-600 dark:text-white\">
</div>

";

            }
        }

        $stub = str_replace(
            ['{{model}}', '{{route}}', '{{routeSingular}}', '{{formFields}}', '{{inputs}}'],
            [$model, $route, $routeSingular, $formFields, $inputs],
            $stub
        );

        $path = resource_path("js/Components/Admin/FormDrawer/{$model}Form.vue");

        if (File::exists($path)) {
            $this->warn("Form already exists.");
            return;
        }

        File::put($path, $stub);
    }
}
