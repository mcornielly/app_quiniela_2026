<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeAdminCrud extends Command
{
    protected $signature = 'make:admin-crud {model} {--smart}';

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

        $columns = collect($columns)
            ->reject(fn($col) => in_array($col, ['id', 'created_at', 'updated_at']))
            ->values()
            ->toArray();

        $smart = $this->option('smart');

        $this->generateController($modelClass, $models, $route, $routeSingular);
        $this->generateIndex($modelClass, $models, $route, $routeSingular, $columns);
        $this->generateForm($modelClass, $routeSingular, $route, $columns, $table, $smart);

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

    protected function generateIndex($model, $models, $route, $routeSingular, $columns)
    {
        $stub = File::get(resource_path('stubs/admin-index.stub'));

        $tableColumns = '';

        foreach ($columns as $column) {
            $label = Str::title(str_replace('_', ' ', $column));

            $tableColumns .= "    { key: '{$column}', label: '{$label}' },\n";
        }

        $stub = str_replace(
            ['{{model}}', '{{models}}', '{{route}}', '{{routeSingular}}', '{{columns}}'],
            [$model, $models, $route, $routeSingular, $tableColumns],
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

    protected function generateForm($model, $routeSingular, $route, $columns, $table, $smart)
    {
        $stub = File::get(resource_path('stubs/admin-form.stub'));

        $formFields = '';
        $inputs = '';

        $imageFields = ['flag', 'flag_path', 'logo', 'image', 'avatar', 'photo'];

        $imports = "
            import TextInput from '@/Components/Admin/Inputs/TextInput.vue'
            import NumberInput from '@/Components/Admin/Inputs/NumberInput.vue'
            import SelectInput from '@/Components/Admin/Inputs/SelectInput.vue'
            import TextareaInput from '@/Components/Admin/Inputs/TextareaInput.vue'
            import CheckboxInput from '@/Components/Admin/Inputs/CheckboxInput.vue'
            import ImageUpload from '@/Components/Admin/Inputs/ImageUpload.vue'
            ";

        foreach ($columns as $column) {
            $formFields .= "    {$column}: props.{$routeSingular}?.{$column} || '',\n";

            $label = Str::title(str_replace('_', ' ', $column));

            $type = $this->detectColumnType($table, $column);

            $smartType = $smart ? $this->detectSmartType($column) : null;

            if ($smartType === 'relation' || Str::endsWith($column, '_id')) {
                $inputs .= "
                    <SelectInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                    />";
            } elseif ($smartType === 'image') {
                $inputs .= "
                    <ImageUpload
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                        :preview=\"{$routeSingular}?.{$column}\"
                    />";
            } elseif ($smartType === 'boolean' || $type === 'boolean') {
                $inputs .= "
                    <CheckboxInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                    />";
            } elseif ($type === 'text') {
                $inputs .= "
                    <TextareaInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                    />";
            } elseif (in_array($type, ['integer', 'bigint', 'decimal', 'float'])) {
                $inputs .= "
                    <NumberInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                    />";
            } elseif ($type === 'date') {
                $inputs .= "
                    <TextInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                        type=\"date\"
                    />";
            } else {
                $inputs .= "
                    <TextInput
                        v-model=\"form.{$column}\"
                        label=\"{$label}\"
                    />";
            }
        }

        $stub = str_replace(
            [
                '{{model}}',
                '{{route}}',
                '{{routeSingular}}',
                '{{formFields}}',
                '{{inputs}}',
                '{{imports}}'
            ],
            [
                $model,
                $route,
                $routeSingular,
                $formFields,
                $inputs,
                $imports
            ],
            $stub
        );

        $path = resource_path("js/Components/Admin/FormDrawer/{$model}Form.vue");

        if (File::exists($path)) {
            $this->warn("Form already exists.");
            return;
        }

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $stub);
    }

    protected function detectColumnType($table, $column)
    {
        try {
            return Schema::getColumnType($table, $column);
        } catch (\Exception $e) {
            return 'string';
        }
    }

    protected function detectSmartType($column)
    {
        $images = ['flag','flag_path','logo','image','avatar','photo'];

        if (Str::endsWith($column, '_id')) {
            return 'relation';
        }

        if (in_array($column, $images)) {
            return 'image';
        }

        if ($column === 'active' || $column === 'enabled') {
            return 'boolean';
        }

        return null;
    }
}
