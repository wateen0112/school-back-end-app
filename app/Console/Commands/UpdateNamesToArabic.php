<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateNamesToArabic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'names:to-arabic {--dry-run : Do not persist changes, only report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store the Arabic name as the English locale value for all translatable models';

    /**
     * All Eloquent models that use Spatie HasTranslations.
     *
     * @var array
     */
    protected $models = [
        \App\Models\Classroom::class,
        \App\Models\Fee::class,
        \App\Models\Gender::class,
        \App\Models\Grade::class,
        \App\Models\InstallmentPlan::class,
        \App\Models\My_Parent::class,
        \App\Models\Nationalitie::class,
        \App\Models\Quizze::class,
        \App\Models\Religion::class,
        \App\Models\Section::class,
        \App\Models\Specialization::class,
        \App\Models\Student::class,
        \App\Models\Subject::class,
        \App\Models\Teacher::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach ($this->models as $modelClass) {
            try {
                $model = new $modelClass();
                $fields = $model->translatable ?? [];
                $total = 0;
                $changed = 0;

                foreach ($modelClass::all() as $record) {
                    $total++;
                    $recordChanged = false;

                    foreach ($fields as $field) {
                        try {
                            $ar = $record->getTranslation($field, 'ar', false);
                        } catch (\Throwable $e) {
                            continue;
                        }

                        if (!is_string($ar) || trim($ar) === '') {
                            continue;
                        }

                        $en = $record->getTranslation($field, 'en', false);

                        if (!is_string($en) || trim($en) !== $ar) {
                            $record->setTranslation($field, 'en', $ar);
                            $recordChanged = true;
                        }
                    }

                    if ($recordChanged) {
                        $changed++;
                        if (!$dryRun) {
                            $record->save();
                        }
                    }
                }

                $this->info(class_basename($modelClass) . ": {$changed}/{$total} records " . ($dryRun ? 'would be updated' : 'updated'));
            } catch (\Throwable $e) {
                $this->error(class_basename($modelClass) . ': ' . $e->getMessage());
            }
        }

        if ($dryRun) {
            $this->info('Dry run complete. Run without --dry-run to persist.');
        } else {
            $this->info('Done.');
        }

        return 0;
    }
}