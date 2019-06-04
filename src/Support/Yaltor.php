<?php

namespace Shanginn\Yalt\Support;

use App;
use Carbon\Carbon;

class Yaltor
{
    /**
     * Available locales
     *
     * @var array
     */
    protected static $locales;

    /**
     * Global defined behavior for using translation fallback
     *
     * @var bool
     */
    protected static $useTranslationFallback;

    /**
     * @throws \Exception
     * @return array
     */
    public static function getLocales()
    {
        if (isset(static::$locales)) {
            return static::$locales;
        }

        $config = config('yalt');

        if (empty($config)) {
            return [];
        }

        $locales = [];

        foreach ($config['locales'] as $key => $locale) {
            if (is_array($locale)) {
                $locales[] = $key;
                foreach ($locale as $countryLocale) {
                    $locales[] = $key . $config['locale_separator'] . $countryLocale;
                }
            } else {
                $locales[] = $locale;
            }
        }

        return static::setLocales($locales);
    }

    protected static function setLocales($locales)
    {
        static::$locales = $locales;

        return $locales;
    }

    /**
     * @return string
     */
    public function getLocaleSeparator()
    {
        return config('translatable.locale_separator', '-');
    }

    /**
     * @return null|string
     */
    public function getLocaleFromRequest()
    {
        return static::isValidLocale($requestLocale = strtolower(\Request::header('Accept-Language'))) ?
            $requestLocale : null;
    }

    /**
     * @param $lang
     * @return string
     */
    public function getSystemLocaleFromLang($lang)
    {
        switch ($lang) {
            case 'ru':
                $systemLocale = 'ru_RU.UTF8';
                break;

            case 'fr':
                $systemLocale = 'fr_FR.UTF8';
                break;
            default:
                $systemLocale = 'en_GB.UTF8';
        }

        return $systemLocale;
    }

    /**
     * @param $locale
     * @return bool
     */
    public static function isValidLocale($locale)
    {
        return in_array($locale, static::getLocales());
    }

    /**
     * @param null $locale
     *
     * @return string
     */
    public function getFallbackLocale($locale = null)
    {
        if ($locale && $this->isLocaleCountryBased($locale)) {
            if ($fallback = $this->getLanguageFromCountryBasedLocale($locale)) {
                return $fallback;
            }
        }

        return config('translatable.fallback_locale');
    }

    /**
     * @param $locale
     *
     * @return bool
     */
    public function isLocaleCountryBased($locale)
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    /**
     * @param $locale
     *
     * @return string
     */
    public function getLanguageFromCountryBasedLocale($locale)
    {
        $parts = explode($this->getLocaleSeparator(), $locale);

        return array_get($parts, 0);
    }

    /**
     * @return bool|null
     */
    public static function useFallback()
    {
        //TODO: set this static var
        return static::$useTranslationFallback ?? config('translatable.use_fallback');
    }


    /**
     * @param $locale
     * @param \Closure $callback
     */
    public function withLocale($locale, \Closure $callback)
    {
        $oldAppLocale = App::getLocale();

        $this->setAllLocales($locale);

        call_user_func($callback);

        $this->setAllLocales($oldAppLocale);
    }

    /**
     * @param $locale
     */
    public function setAllLocales($locale)
    {
        $locale = strtolower($locale);
        $systemLocale = \Yalt::getSystemLocaleFromLang($locale);

        App::setLocale($locale);
        Carbon::setLocale($locale);
        setlocale(LC_ALL, $systemLocale);
    }
}
