<?php

namespace App\Searcher;

use Wamania\Snowball\Stemmer;

/**
 * Class Boolify
 *
 * Extracted and refactored from the project 'FULLTEXT-search-utility'
 * by Ovais Tariq (https://github.com/ovaistariq)
 *
 * @package App\Searcher
 */
class Boolify
{
    const SEARCH_MINIMUM_WORD_LENGTH = 3;

    const SEARCH_WORD_OR = "or";

    const SEARCH_WORD_AND = "and";

    const SEARCH_WORD_AND_OPERATOR = "+";

    const SEARCH_WORD_BEGINNING_WITH_OPERATOR = "*";

    const SEARCH_BUILD_QUERYSTRING_REGEX = '/"[^"]+"|[^"\s,]+/';

    private $stemmer;

    private $queryString;

    /**
     * Boolify constructor.
     * @param Stemmer $stemmer
     * @param $queryString
     */
    public function __construct(Stemmer $stemmer, $queryString)
    {
        $this->stemmer = $stemmer;

        $this->queryString = strtolower(trim($queryString));
    }

    /**
     * @return string
     */
    public function getSearchQueryString()
    {
        $searchQueryWordsArray = $this->getSearchQueryArray();

        return implode(" ", $searchQueryWordsArray);
    }

    /**
     * @return array|bool
     */
    public function getSearchQueryArray()
    {
        preg_match_all(self::SEARCH_BUILD_QUERYSTRING_REGEX, $this->queryString, $wordsArray);

        if (empty($wordsArray)) {
            return false;
        }

        return $this->buildSearchQuery($this->stemmer, $wordsArray);
    }

    /**
     * @param Stemmer $stemmer
     * @param $wordsArray
     * @param array $keywordsArray
     * @return array
     */
    protected function buildSearchQuery(Stemmer $stemmer, $wordsArray, $keywordsArray = array())
    {
        if (is_array($wordsArray) && count($wordsArray) > 0) {
            $keywordCount = 0;
            $prependAndOperator = false;

            foreach ($wordsArray as $word) {
                // if the word is a list of words and not a single word iterate over the entire list
                if (is_array($word) && count($word) > 0) {
                    return $this->buildSearchQuery($stemmer, $word, $keywordsArray);
                }

                // if the word is an empty list skip it
                if (is_array($word) && count($word) < 1) {
                    continue;
                }

                // if the length of the word does not match the minimum threshold skip it
                if (strlen($word) < self::SEARCH_MINIMUM_WORD_LENGTH) {
                    continue;
                }

                // if the word is already present in the list skip it
                $stemmedWord = $stemmer->stem($word);

                $patterns = [
                    $word, "+$word", "$word*", "+$word*",
                    $stemmedWord, "+$stemmedWord", "$stemmedWord*", "+$stemmedWord*"
                ];

                foreach ($patterns as $pattern) {
                    if (in_array($pattern, $keywordsArray)) {
                        continue 2;
                    }
                }

                // if the word is an 'or' skip it
                if ($word == self::SEARCH_WORD_OR) {
                    continue;
                }

                // if the word is an 'and' prepend a '+' to neighbouring words
                if ($word == self::SEARCH_WORD_AND) {
                    //prepend the operator '+' to the previous word if the word already doesnt contain '+' in the beginning
                    if (($keywordCount > 0) && (!strstr($keywordsArray[$keywordCount - 1], self::SEARCH_WORD_AND_OPERATOR) || (strpos($keywordsArray[$keywordCount - 1], self::SEARCH_WORD_AND_OPERATOR) > 0))) {
                        $keywordsArray[$keywordCount - 1] = self::SEARCH_WORD_AND_OPERATOR . $keywordsArray[$keywordCount - 1];
                    }

                    // prepend the operator '+' to the next word if the word already doesnt contain '+' in the beginning
                    $prependAndOperator = true;

                    continue;
                }

                if ($prependAndOperator) {
                    if (!strstr($word, self::SEARCH_WORD_AND_OPERATOR) || (strpos($word, self::SEARCH_WORD_AND_OPERATOR) > 0)) {
                        $word = self::SEARCH_WORD_AND_OPERATOR . $word;
                    }

                    $prependAndOperator = false;
                }

                // if the word is a phrase leave it as it is else stem the word
                if (!self::isPhrase($word)) {
                    $word = $stemmer->stem($word) . self::SEARCH_WORD_BEGINNING_WITH_OPERATOR;
                }

                // add the word to our keywords list
                $keywordsArray[] = $word;
                $keywordCount++;
            }
        }

        return $keywordsArray;
    }

    /**
     * @param $word
     * @return bool
     */
    protected function isPhrase($word)
    {
        $isPhrase = false;

        $charactersCount = count_chars($word, 1);

        if (is_array($charactersCount)) {
            foreach ($charactersCount as $character => $count) {
                if (chr($character) == "\"" && $count == 2) {
                    $isPhrase = true;
                    break;
                }
            }
        }

        return $isPhrase;
    }
}
