<?php
/**
 *
 * Created by:  Milan Simek
 * Company:     Plugin Company
 *
 * LICENSE: http://plugin.company/docs/magento-extensions/magento-extension-license-agreement
 *
 * YOU WILL ALSO FIND A PDF COPY OF THE LICENSE IN THE DOWNLOADED ZIP FILE
 *
 * FOR QUESTIONS AND SUPPORT
 * PLEASE DON'T HESITATE TO CONTACT US AT:
 *
 * SUPPORT@PLUGIN.COMPANY
 *
 */
namespace DataMigrator;

class FieldNormalizer
{
    private $textFields = [
        'street',
        'city',
        'region',
        'company',
        'postcode'
    ];

    private $nameFields = [
        'firstname',
        'middlename',
        'lastname'
    ];

    private $phoneFields = [
        'telephone',
        'fax',
    ];

    public function __construct(
    ) {
        setlocale(LC_CTYPE, 'en_US.UTF-8');
    }

    public function normalizeField($key, $value)
    {
        if(in_array($key, $this->textFields)) {
            return $this->normalizeText($value);
        }
        if(in_array($key, $this->nameFields)) {
            return $this->normalizeName($value);
        }
        if(in_array($key, $this->phoneFields)) {
            return $this->normalizePhone($value);
        }
        return false;
    }

    public function normalizePhone($text)
    {
        $text = (string)$text;
        return preg_replace('/[^\d]/u', '', $text);
    }

    public function normalizeName($text)
    {
        return preg_replace('/[^\p{L}]/u', '', $this->normalizeText($text));
    }

    public function normalizeText($text)
    {
        $text = (string)$text;
        $text = $this->removeSpecialCharactersAndWhiteSpace($text);
        $text = $this->translit($text);
        return $text;
    }

    private function removeSpecialCharactersAndWhiteSpace($text)
    {
        return preg_replace('/[^\p{L}\d]/u', '', $text);
    }

    private function translit($text)
    {
        if(function_exists('transliterator_transliterate')) {
            $text = str_replace(' ' , '', transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $text));
        }
        $textArray = $this->convertStringToArray($text);
        $result = "";
        foreach($textArray as $key => $character)
        {
            $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $character);
            if($converted == '?') {
                $converted = $character;
            }
            $result .= $converted;
        }
        return $result;
    }

    private function convertStringToArray($text)
    {
        return preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY);
    }

}