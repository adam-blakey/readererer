<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFeatureFlagsRequest;
use App\Models\Ensemble;
use App\Settings\GeneralSettings;
use App\Support\FeatureFlag;
use Laravel\Pennant\Feature;

class FeatureFlagController extends Controller
{
    public function edit()
    {
        return view('feature-flags.edit', [
            'page_name' => __('Feature flags'),
            'flags' => $this->flags(),
            'ensembles' => Ensemble::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function update(UpdateFeatureFlagsRequest $request, GeneralSettings $settings)
    {
        $settings->feature_flags = collect($this->flags())->map(fn (FeatureFlag $flag, string $class) => [
            'enabled' => $request->boolean("flags.$class.enabled"),
            'roles' => array_map('intval', $request->validated("flags.$class.roles", [])),
            'ensembles' => array_map('intval', $request->validated("flags.$class.ensembles", [])),
            'starts_at' => $request->validated("flags.$class.starts_at"),
            'from_version' => $request->validated("flags.$class.from_version"),
        ])->all();
        $settings->save();

        return redirect()->route('feature-flags.edit')->with('status', __('Feature flags saved.'));
    }

    /** @return array<class-string<FeatureFlag>, FeatureFlag> */
    private function flags(): array
    {
        return collect(Feature::defined())
            ->map(fn (string $name) => Feature::instance($name))
            ->filter(fn ($flag) => $flag instanceof FeatureFlag)
            ->keyBy(fn (FeatureFlag $flag) => $flag::class)
            ->sortBy(fn (FeatureFlag $flag) => $flag->title())
            ->all();
    }
}
