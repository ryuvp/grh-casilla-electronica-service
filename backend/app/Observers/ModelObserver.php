<?php

namespace App\Observers;

use App\Helpers\LogHelper;

class ModelObserver
{
    public function created($model)
    {
        LogHelper::audit($model, 'created', auth()->user());
    }

    public function updated($model)
    {
        LogHelper::audit($model, 'updated', auth()->user());
    }

    public function deleted($model)
    {
        LogHelper::audit($model, 'deleted', auth()->user());
    }

    public function restored($model)
    {
        LogHelper::audit($model, 'restored', auth()->user());
    }
}

