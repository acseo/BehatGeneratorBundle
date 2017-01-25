<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Util;

/**
 * ApiEndpointGuesser guess endpoint for a given entity name in a REST context
 * According to the most common pratice, which is:
 *     - Replace camel case by underscore
 *     - Pluralize the last part
 *     - e.g: BlogPost => /blog_posts
 */
class ApiEndpointGuesser
{
    /**
     * Plural inflector rules.
     *
     * @var array
     */
    protected static $_plural = [
        '/(s)tatus$/i' => '\1tatuses',
        '/(quiz)$/i' => '\1zes',
        '/^(ox)$/i' => '\1\2en',
        '/([m|l])ouse$/i' => '\1ice',
        '/(matr|vert|ind)(ix|ex)$/i' => '\1ices',
        '/(x|ch|ss|sh)$/i' => '\1es',
        '/([^aeiouy]|qu)y$/i' => '\1ies',
        '/(hive)$/i' => '\1s',
        '/(chef)$/i' => '\1s',
        '/(?:([^f])fe|([lre])f)$/i' => '\1\2ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '\1a',
        '/(p)erson$/i' => '\1eople',
        '/(?<!u)(m)an$/i' => '\1en',
        '/(c)hild$/i' => '\1hildren',
        '/(buffal|tomat)o$/i' => '\1\2oes',
        '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin)us$/i' => '\1i',
        '/us$/i' => 'uses',
        '/(alias)$/i' => '\1es',
        '/(ax|cris|test)is$/i' => '\1es',
        '/s$/' => 's',
        '/^$/' => '',
        '/$/' => 's',
    ];

    /**
     * Irregular rules.
     *
     * @var array
     */
    protected static $_irregular = [
        'atlas' => 'atlases',
        'beef' => 'beefs',
        'brief' => 'briefs',
        'brother' => 'brothers',
        'cafe' => 'cafes',
        'child' => 'children',
        'cookie' => 'cookies',
        'corpus' => 'corpuses',
        'cow' => 'cows',
        'criterion' => 'criteria',
        'ganglion' => 'ganglions',
        'genie' => 'genies',
        'genus' => 'genera',
        'graffito' => 'graffiti',
        'hoof' => 'hoofs',
        'loaf' => 'loaves',
        'man' => 'men',
        'money' => 'monies',
        'mongoose' => 'mongooses',
        'move' => 'moves',
        'mythos' => 'mythoi',
        'niche' => 'niches',
        'numen' => 'numina',
        'occiput' => 'occiputs',
        'octopus' => 'octopuses',
        'opus' => 'opuses',
        'ox' => 'oxen',
        'penis' => 'penises',
        'person' => 'people',
        'sex' => 'sexes',
        'soliloquy' => 'soliloquies',
        'testis' => 'testes',
        'trilby' => 'trilbys',
        'turf' => 'turfs',
        'potato' => 'potatoes',
        'hero' => 'heroes',
        'tooth' => 'teeth',
        'goose' => 'geese',
        'foot' => 'feet',
        'foe' => 'foes',
        'sieve' => 'sieves',
    ];

    /**
     * Method cache array.
     *
     * @var array
     */
    protected static $_cache = [];

    /**
     * Guess api endpoint for a given entity name.
     *
     * @param  string $entityName
     * @return string
     */
    public function guessEndpoint($entityName)
    {
        $endpoint = '/'.$entityName;
        $endpoint = $this->underscore($endpoint);
        $endpoint = $this->pluralize($endpoint);

        return $endpoint;
    }

    /**
     * Cache inflected values, and return if already available.
     *
     * @param string      $type  Inflection type
     * @param string      $key   Original value
     * @param string|bool $value Inflected value
     *
     * @return string|bool Inflected value on cache hit or false on cache miss.
     */
    protected static function _cache($type, $key, $value = false)
    {
        $key = '_'.$key;
        $type = '_'.$type;
        if ($value !== false) {
            static::$_cache[$type][$key] = $value;

            return $value;
        }
        if (!isset(static::$_cache[$type][$key])) {
            return false;
        }

        return static::$_cache[$type][$key];
    }

    /**
     * Return $word in plural form.
     *
     * @param string $word Word in singular
     *
     * @return string Word in plural
     *
     * @link http://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-plural-singular-forms
     */
    protected static function pluralize($word)
    {
        if (isset(static::$_cache['pluralize'][$word])) {
            return static::$_cache['pluralize'][$word];
        }
        if (!isset(static::$_cache['irregular']['pluralize'])) {
            static::$_cache['irregular']['pluralize'] = '(?:'.implode('|', array_keys(static::$_irregular)).')';
        }
        if (preg_match('/(.*?(?:\\b|_))('.static::$_cache['irregular']['pluralize'].')$/i', $word, $regs)) {
            static::$_cache['pluralize'][$word] = $regs[1].substr($regs[2], 0, 1).
                substr(static::$_irregular[strtolower($regs[2])], 1);

            return static::$_cache['pluralize'][$word];
        }
        foreach (static::$_plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                static::$_cache['pluralize'][$word] = preg_replace($rule, $replacement, $word);

                return static::$_cache['pluralize'][$word];
            }
        }
    }

    /**
     * Returns the input CamelCasedString as an underscored_string.
     *
     * Also replaces dashes with underscores
     *
     * @param string $string CamelCasedString to be "underscorized"
     *
     * @return string underscore_version of the input string
     *
     * @link http://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-camelcase-and-under-scored-forms
     */
    protected static function underscore($string)
    {
        return static::delimit(str_replace('-', '_', $string), '_');
    }

    /**
     * Expects a CamelCasedInputString, and produces a lower_case_delimited_string.
     *
     * @param string $string    String to delimit
     * @param string $delimiter the character to use as a delimiter
     *
     * @return string delimited string
     */
    protected static function delimit($string, $delimiter = '_')
    {
        $cacheKey = __FUNCTION__.$delimiter;
        $result = static::_cache($cacheKey, $string);
        if ($result === false) {
            $result = mb_strtolower(preg_replace('/(?<=\\w)([A-Z])/', $delimiter.'\\1', $string));
            static::_cache($cacheKey, $string, $result);
        }

        return $result;
    }
}
