<?php
namespace Taco\Util;

/**
 * Convenience methods for handling strings
 * @version 0.1
 */
class Str
{

    /**
     * Humanize the string
     * @param string $str
     * @return string
     */
    public static function human($str)
    {
        // Cleanup
        $out = str_replace('_', ' ', $str);
        $out = ucwords(strtolower($out));
        $out = preg_replace('/\s{2,}/', ' ', $out);
        $out = preg_replace('/^\s/', '', $out);
        $out = preg_replace('/\s$/', '', $out);
        if (strlen($out) === 0) {
            return $out;
        }

        // Gather stopwords before looping
        $stop_words_lower = self::stopWordsLower();

        // Handle each word
        $words = explode(' ', $out);
        $out_words = array();
        foreach ($words as $n => $word) {
            $out_word = $word;

            // If we have a special match, don't do anything else
            $specials = array(
                '/^id$/i'   => 'ID',
                '/^ids$/i'  => 'IDs',
                '/^url$/i'  => 'URL',
                '/^urls$/i' => 'URLs',
                '/^cta$/i'  => 'CTA',
                '/^api$/i'  => 'API',
                '/^faq$/i'  => 'FAQ',
                '/^ip$/i'   => 'IP',
                '/^why$/'   => 'why',
                '/^Why$/'   => 'Why',
            );
            $special_word = false;
            foreach ($specials as $regex => $special) {
                if (!preg_match($regex, $word)) {
                    continue;
                }

                $special_word = true;
                $out_word = $special;
            }
            if ($special_word) {
                $out_words[] = $out_word;
                continue;
            }

            // Handle acronyms without vowels
            if (!preg_match('/[aeiou]/i', $word)) {
                $out_word = strtoupper($out_word);
            }

            // Stop words
            $lower = strtolower($word);
            if (in_array($lower, $stop_words_lower) && $n !== 0) {
                $out_word = $lower;
            }

            $out_words[] = $out_word;
        }
        $out = join(' ', $out_words);

        // Questions
        $first_word_lower = strtolower($words[0]);
        $first_word_lower_no_contraction = preg_replace("/'s$/", '', $first_word_lower);
        $is_question = in_array($first_word_lower_no_contraction, self::questionWords());
        $has_question_mark = (bool) preg_match('/\?+$/', $out);
        if ($is_question && !$has_question_mark) {
            $out .= '?';
        }

        return $out;
    }


    /**
     * Machinize the string
     * @param string $str
     * @param string $separator
     * @return string
     */
    public static function machine($str, $separator = '_')
    {
        $out = strtolower($str);
        $out = preg_replace('/[^a-z0-9' . $separator . ']/', $separator, $out);
        $out = preg_replace('/' . $separator . '{2,}/', $separator, $out);
        $out = preg_replace('/^' . $separator . '/', '', $out);
        $out = preg_replace('/' . $separator . '$/', '', $out);
        return $out;
    }
    
    
    /**
     * Mechanize
     * This is an improved version of machine() that transliterates accented
     * characters and removes apostrophes
     * @param string $str
     * @param string $separator
     * @return string
     */
    public static function mechanize($str, $separator = '_')
    {
        $out = strtolower($str);
        $out = self::transliterate($out);
        $out = preg_replace(
            array('/[\'’]/', '/[^a-zA-Z0-9\s_]/', '/[\s_]+/', '/^_|_$/'),
            array('', '_', '_', ''),
            $out
        );
        if ($separator !== '_') {
            $out = str_replace('_', $separator, $out);
        }
        return $out;
    }
    
    
    /**
     * Transliterate
     * @param string $str
     * @return string
     */
    public static function transliterate($str)
    {
        $transliterations = array(
            'a' => 'áăâäàāąåãǻǎ',
            'ae' => 'æǽ',
            'c' => 'ćčçĉċ',
            'd' => 'ðďđ',
            'e' => 'éĕěêëėèēę',
            'g' => 'ğĝģġ',
            'h' => 'ħĥ',
            'i' => 'ıíĭîïìīįĩǐ',
            'ij' => 'ĳ',
            'j' => 'ȷĵ',
            'k' => 'ķ',
            'l' => 'ĺľļŀł',
            'n' => 'ńňņñ',
            'ng' => 'ŋ',
            'o' => 'óŏôöòőōøõǿǒơ',
            'oe' => 'œ',
            'r' => 'ŕřŗ',
            's' => 'śšşŝș',
            'ss' => 'ß',
            't' => 'ŧťţț',
            'th' => 'þ',
            'u' => 'úŭûüùűūųůũǔǖǘǚǜư',
            'w' => 'ẃŵẅẁ',
            'y' => 'ýŷÿỳ',
            'z' => 'źžż',
            
            'A' => 'ÁĂÂÄÀĀĄÅÃǺǍ',
            'Ae' => 'ÆǼ',
            'C' => 'ĆČÇĈĊ',
            'D' => 'ÐĎĐ',
            'E' => 'ÉĔĚÊËĖÈĒĘ',
            'G' => 'ĞĜĢĠ',
            'H' => 'ĦĤ',
            'I' => 'IÍĬÎÏİÌĪĮĨǏ',
            'Ij' => 'Ĳ',
            'J' => 'Ĵ',
            'K' => 'Ķ',
            'L' => 'ĹĽĻĿŁ',
            'N' => 'ŃŇŅÑ',
            'Ng' => 'Ŋ',
            'O' => 'ÓŎÔÖÒŐŌØÕǾǑƠ',
            'Oe' => 'Œ',
            'R' => 'ŔŘŖ',
            'S' => 'ŚŠŞŜȘ',
            // 'SS' => 'SS',
            'T' => 'ŦŤŢȚ',
            'Th' => 'Þ',
            'U' => 'ÚŬÛÜÙŰŪŲŮŨǓǕǗǙǛƯ',
            'W' => 'ẂŴẄẀ',
            'Y' => 'ÝŶŸỲ',
            'Z' => 'ŹŽŻ',
            
            '1' => '¹',
            '2' => '²',
            '3' => '³',
            '4' => '⁴',
        );
        
        $originals = array_map(function($el){
            return '/[' . $el . ']/u';
        }, array_values($transliterations));
        $out = preg_replace($originals, array_keys($transliterations), $str);
        return $out;
    }


    /**
     * Convert to snake case
     * @param string $str
     * @return string
     */
    public static function snake($str)
    {
        return self::mechanize($str, '_');
    }


    /**
     * Convert to kebab case
     * @param string $str
     * @return string
     */
    public static function kebab($str)
    {
        return self::mechanize($str, '-');
    }


    /**
     * Convert to chain case
     * @param string $str
     * @return string
     */
    public static function chain($str)
    {
        return self::kebab($str);
    }
    
    
    /**
     * Convert to Pascal case
     *
     * Forcing will remove any existing uppercase
     * Example: "Fetch HTML"
     * - Without force: "FetchHTML"
     * - With force: "FetchHtml"
     *
     * @param string $str
     * @param bool $force
     * @return string
     */
    public static function pascal($str, $force = false)
    {
        $words = ($force)
            ? explode(' ', self::mechanize($str, ' '))
            : preg_split('/[\W_]/', self::transliterate($str));
        
        $words = array_map('ucfirst', $words);
        return join('', $words);
    }
    
    
    /**
     * Convert to camel case
     * @param string $str
     * @param bool $force
     * @return string
     */
    public static function camel($str, $force = false) {
        return lcfirst(self::pascal($str, $force));
    }
    
    
    /**
     * Convert to screaming snake case
     * @param string $str
     * @return string
     */
    public static function scream($str)
    {
        return strtoupper(self::snake($str));
    }
    
    
    /**
     * Convert to constant case
     * @param string $str
     * @return string
     */
    public static function constant($str)
    {
        return self::scream($str);
    }
    
    
    /**
     * Convert between two string formats
     * @param string $str
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function convert($str, $from, $to)
    {
        if ($from === 'camel' && $to === 'human') {
            $human = preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
            $human = preg_replace('/(\D)?(\d+)(\D)?/', '$1 $2 $3', $human);
            return trim($human);
        }
        
        if (in_array($from, array('machine', 'snake', 'chain', 'kebab')) && $to === 'camel') {
            $separators = preg_replace('/[^_-]/', '', $str);
            if (!strlen($separators)) {
                return ucfirst($str);
            }
            $split = explode(substr($separators, 0, 1), $str);
            $split = array_map('ucfirst', $split);
            return join('', $split);
        }
    }


    /**
     * Get an array of stop words
     * Stop words are words which are filtered out prior to, or after, processing of natural language data
     * @link http://www.textfixer.com/resources/common-english-words.txt
     * @return array
     */
    public static function stopWords()
    {
        return array('a', 'able', 'about', 'across', 'after', 'all', 'almost', 'also', 'am', 'among', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'because', 'been', 'but', 'by', 'can', 'cannot', 'could', 'dear', 'did', 'do', 'does', 'either', 'else', 'ever', 'every', 'for', 'from', 'get', 'got', 'had', 'has', 'have', 'he', 'her', 'hers', 'him', 'his', 'how', 'however', 'i', 'if', 'in', 'into', 'is', 'it', 'its', 'just', 'least', 'let', 'like', 'likely', 'may', 'me', 'might', 'most', 'must', 'my', 'neither', 'no', 'nor', 'not', 'of', 'off', 'often', 'on', 'only', 'or', 'other', 'our', 'own', 'rather', 'said', 'say', 'says', 'she', 'should', 'since', 'so', 'some', 'than', 'that', 'the', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'tis', 'to', 'too', 'twas', 'us', 'wants', 'was', 'we', 'were', 'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'would', 'yet', 'you', 'your');
    }
    
    
    /**
     * Get an array of stop words that should typically be lowercase in the middle of a phrase
     * @return array
     */
    public static function stopWordsLower()
    {
        return array('a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'if', 'in', 'nor', 'of', 'on', 'or', 'the', 'to');
    }


    /**
     * Get an array of words that start questions
     * @link http://www.hopstudios.com/nep/unvarnished/item/list_of_english_question_words
     * @return array
     */
    public static function questionWords()
    {
        return array('who', 'what', 'where', 'when', 'why', 'how', 'which', 'wherefore', 'whatever', 'whom', 'whose', 'wherewith', 'whither', 'whence');
    }
    
    
    /**
     * Shorten a string
     * @param string $input
     * @param integer $num_words
     * @param string $hellip
     * @return string
     */
    public static function shortenWords($input, $num_words = 35, $hellip = '&hellip;')
    {
        $words = explode(' ', $input);
        return (count($words) <= $num_words)
            ? join(' ', $words)
            : join(' ', array_slice($words, 0, $num_words)) . $hellip;
    }


    /**
     * Shorten a string by character limit, preserving words
     * @param string $input
     * @param integer $num_chars
     * @param string $hellip
     * @return string
     */
    public static function shortenWordsByChar($input, $num_chars = 35, $hellip = '&nbsp;&hellip;')
    {
        if (strlen($input) <= $num_chars) {
            return $input;
        }

        $shortened = substr($input, 0, $num_chars);
        $shortened_words = array_filter(explode(' ', $shortened));
        end($shortened_words);
        $last_key = key($shortened_words);

        $words = explode(' ', $input);
        $words = array_slice($words, 0, $last_key + 1);
        if ($words[$last_key] !== $shortened_words[$last_key]) {
            unset($words[$last_key]);
        }
        return join(' ', $words) . $hellip;
    }
    
    
    /**
     * Camel case to human
     * @param string $input
     * @return string
     */
    public static function camelToHuman($input)
    {
        return preg_replace('/([a-z])([A-Z])/', "$1 $2", $input);
    }
    
    
    /**
     * Replace non-breaking spaces with regular spaces
     * The fourth item in $search_spaces is a pasted non-breaking space
     * @param string $input
     * @return string
     */
    public static function cleanSpaces($input)
    {
        $nonbreaking_space = chr(0xC2) . chr(0xA0);
        $search_spaces = ['<p>&nbsp;</p>', '&nbsp;', $nonbreaking_space, ' '];
        $replace_spaces = ['', ' ', ' ', ' '];
        $out = str_replace($search_spaces, $replace_spaces, $input);
        $out = trim(preg_replace('/\s{2,}/', ' ', $out));
        return $out;
    }
    
}
