<?php

namespace OptimistDigital\MultiselectField;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Multiselect extends Field
{
    public $component = 'multiselect-field';

    protected $pageResponseResolveCallback;
    protected $saveAsJSON = false;

    /**
     * Sets the options available for select.
     *
     * @param  array|callable
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function options($options = [])
    {
        if (is_callable($options)) $options = call_user_func($options);

        return $this->withMeta([
            'options' => collect($options)->map(function ($label, $value) {
                return is_array($label) ? $label + ['value' => $value] : ['label' => $label, 'value' => $value];
            })->values()->all(),
        ]);
    }

    protected function resolveAttribute($resource, $attribute)
    {
        $singleSelect = $this->meta['singleSelect'] ?? false;
        $value = data_get($resource, str_replace('->', '.', $attribute));
        if ($singleSelect) return json_decode($value);
        return $this->saveAsJSON ? $value : (is_array($value) ? $value : json_decode($value));
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        $singleSelect = $this->meta['singleSelect'] ?? false;
        $value = $request->input($requestAttribute) ?? null;

        if ($singleSelect) {
            $model->{$attribute} = $value;
        } else {
            $model->{$attribute} = $this->saveAsJSON ? $value : json_encode($value);
        }
    }

    /**
     * Allows the field to save an actual JSON array to a SQL JSON column.
     *
     * @param bool $saveAsJSON
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function saveAsJSON($saveAsJSON = true)
    {
        $this->saveAsJSON = $saveAsJSON;
        return $this;
    }

    /**
     * Sets the max number of options the user can select.
     *
     * @param int $max
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function max($max)
    {
        return $this->withMeta(['max' => $max]);
    }

    /**
     * Sets the placeholder value displayed on the field.
     *
     * @param string $placeholder
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function placeholder($placeholder)
    {
        return $this->withMeta(['placeholder' => $placeholder]);
    }

    /**
     * Sets the maximum number of options displayed at once.
     *
     * @param int $optionsLimit
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function optionsLimit($optionsLimit)
    {
        return $this->withMeta(['optionsLimit' => $optionsLimit]);
    }

    /**
     * Enables or disables reordering of the field values.
     *
     * @param bool $reorderable
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function reorderable($reorderable = true)
    {
        return $this->withMeta(['reorderable' => $reorderable]);
    }

    /**
     * Enables the field to be used as a single select.
     *
     * This forces the value saved to be a single value and not an array.
     *
     * @param bool $singleSelect
     * @return \OptimistDigital\MultiselectField\Multiselect
     **/
    public function singleSelect($singleSelect = true)
    {
        return $this->withMeta(['singleSelect' => $singleSelect]);
    }

    public function resolveResponseValue($value, $templateModel)
    {
        $parsedValue = isset($value) ? ($this->saveAsJSON ? $value : json_decode($value)) : null;
        return is_callable($this->pageResponseResolveCallback)
            ? call_user_func($this->pageResponseResolveCallback, $parsedValue, $templateModel)
            : $parsedValue;
    }

    public function resolveForPageResponseUsing(callable $resolveCallback)
    {
        $this->pageResponseResolveCallback = $resolveCallback;
        return $this;
    }
}
