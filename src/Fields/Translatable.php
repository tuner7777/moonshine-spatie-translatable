<?php

namespace VI\MoonShineSpatieTranslatable\Fields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Json;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;

class Translatable extends Json
{
    protected array $languagesCodes = [
        "af", "sq", "am", "ar", "an", "hy", "ast", "az", "eu", "be", "bn", "bs", "br", "bg", "ca", "ckb", "zh", "zh-hk", "zh-cn", "zh-tw", "co", "hr", "cs", "da", "nl", "en", "en-au", "en-ca", "en-in", "en-nz", "en-za", "en-gb", "en-us", "eo", "et", "fo", "fil", "fi", "fr", "fr-ca", "fr-fr", "fr-ch", "gl", "ka", "de", "de-at", "de-de", "de-li", "de-ch", "el", "gn", "gu", "ha", "haw", "he", "hi", "hu", "is", "id", "ia", "ga", "it", "it-it", "it-ch", "ja", "kn", "kk", "km", "ko", "ku", "ky", "lo", "la", "lv", "ln", "lt", "mk", "ms", "ml", "mt", "mr", "mn", "ne", "no", "nb", "nn", "oc", "or", "om", "ps", "fa", "pl", "pt", "pt-br", "pt-pt", "pa", "qu", "ro", "mo", "rm", "ru", "gd", "sr", "sh", "sn", "sd", "si", "sk", "sl", "so", "st", "es", "es-ar", "es-419", "es-mx", "es-es", "es-us", "su", "sw", "sv", "tg", "ta", "tt", "te", "th", "ti", "to", "tr", "tk", "tw", "uk", "ur", "ug", "uz", "vi", "wa", "cy", "fy", "xh", "yi", "yo", "zu",
    ];

    protected array $requiredLanguagesCodes = [];

    protected array $priorityLanguagesCodes = [];

    protected bool $keyValue = true;

    /**
     * @param array $languages
     * @return $this
     */
    public function requiredLanguages(array $languages): static
    {
        sort($languages);
        $this->requiredLanguagesCodes = $languages;

        return $this;
    }

    /**
     * @param array $languages
     * @return $this
     */
    public function priorityLanguages(array $languages): static
    {
        sort($languages);
        $this->priorityLanguagesCodes = $languages;

        return $this;
    }

    protected function getLanguagesCodes(): array
    {
        sort($this->languagesCodes);

        return collect(array_combine($this->requiredLanguagesCodes, $this->requiredLanguagesCodes))
            ->merge(array_combine($this->priorityLanguagesCodes, $this->priorityLanguagesCodes))
            ->merge(array_combine($this->languagesCodes, $this->languagesCodes))
            ->toArray();
    }

    public function keyValue(string $key = 'Language', string $value = 'Value'): static
    {
        $this->fields([
            Text::make($key, 'key'),
            Text::make($value, 'value'),
        ]);

        return $this;
    }

    public function getFields(): Fields
    {

        if (empty($this->fields)) {
            $this->fields([

                Text::make(__('Code'), 'key'),
                Text::make(__('Value'), 'value'),

            ]);
        }

        return parent::getFields();
    }

    public function hasFields(): bool
    {
        return true;
    }

    public function indexViewValue(Model $item, bool $container = false): string
    {
        $columns = [];

        $values = collect($item->getTranslations($this->field()))
            ->map(fn ($value, $key) => ['key' => $key, 'value' => $value])
            ->values();

        foreach ($this->getFields() as $field) {
            $columns[$field->field()] = $field->label();
        }

        return view('moonshine::ui.table', [
            'columns' => $columns,
            'values' => $values,
        ]);
    }

    public function exportViewValue(Model $item): string
    {
        return $item->getTranslation($this->field());
    }

    public function formViewValue(Model $item): mixed
    {

        return collect($item->getTranslations($this->field()))
            ->toArray();
    }

    /**
     * @throws ValidationException
     */
    public function save(Model $item): Model
    {
        if ($this->isCanSave() && $this->requestValue() !== false) {
            $array = collect($this->requestValue())
                ->filter(fn ($data) => ! empty($data['key']) && ! empty($data['value']))
                ->mapWithKeys(fn ($data) => [$data['key'] => $data['value']])
                ->toArray();

            $notSetLanguages = array_diff($this->requiredLanguagesCodes, array_keys($array));

            if (! empty($notSetLanguages)) {
                throw ValidationException::withMessages(
                    [$this->field() =>
                        sprintf('The field %s does not have translation values set for the following languages: %s', $this->label(), implode(', ', $notSetLanguages)), ]
                );
            }

            $item->{$this->field()} = $array;
        }

        return $item;
    }
}