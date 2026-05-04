<?php
/**
 * ORM класс для таблицы версий текстов согласий
 */

namespace Untitlet\Journal\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\BooleanField;

class TextVersionTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return 'b_untitlet_journal_text_version';
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            
            new StringField('CODE', [
                'size' => 64,
                'required' => true
            ]),
            
            new IntegerField('VERSION', [
                'required' => true,
                'default_value' => 1
            ]),
            
            new TextField('TEXT_HTML', [
                'nullable' => true
            ]),
            
            new TextField('TEXT_PLAIN', [
                'nullable' => true
            ]),
            
            new BooleanField('IS_ACTIVE', [
                'required' => true,
                'default_value' => false,
                'values' => ['N', 'Y']
            ]),
            
            new DatetimeField('CREATED_AT', [
                'required' => true,
                'default_value' => new \Bitrix\Main\DB\SqlExpression('NOW()')
            ])
        ];
    }

    /**
     * Получить активную версию текста по коду
     * 
     * @param string $code
     * @return array|null
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getActiveVersion($code)
    {
        $result = static::getList([
            'filter' => [
                '=CODE' => $code,
                '=IS_ACTIVE' => 'Y'
            ],
            'limit' => 1
        ])->fetch();
        
        return $result ?: null;
    }

    /**
     * Создать новую версию текста (автоматически деактивирует старую)
     * 
     * @param string $code
     * @param string $textHtml
     * @param string|null $textPlain
     * @return int ID новой версии
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function createVersion($code, $textHtml, $textPlain = null)
    {
        global $DB;
        
        // Начинаем транзакцию
        $DB->StartTransaction();
        
        try {
            // Деактивируем все текущие активные версии для этого кода
            static::update(null, ['IS_ACTIVE' => 'N'], [
                'filter' => [
                    '=CODE' => $code,
                    '=IS_ACTIVE' => 'Y'
                ]
            ]);
            
            // Получаем максимальную версию
            $maxVersion = static::getList([
                'filter' => ['=CODE' => $code],
                'select' => ['VERSION'],
                'order' => ['VERSION' => 'DESC'],
                'limit' => 1
            ])->fetch();
            
            $newVersion = ($maxVersion ? (int)$maxVersion['VERSION'] : 0) + 1;
            
            // Создаем новую активную версию
            $addResult = static::add([
                'CODE' => $code,
                'VERSION' => $newVersion,
                'TEXT_HTML' => $textHtml,
                'TEXT_PLAIN' => $textPlain,
                'IS_ACTIVE' => 'Y',
                'CREATED_AT' => new \Bitrix\Main\Type\DateTime()
            ]);
            
            if ($addResult->isSuccess()) {
                $DB->Commit();
                return $addResult->getId();
            } else {
                $DB->Rollback();
                throw new \Bitrix\Main\ArgumentException(
                    implode(', ', $addResult->getErrorMessages())
                );
            }
        } catch (\Exception $e) {
            $DB->Rollback();
            throw $e;
        }
    }

    /**
     * Получить текст активной версии
     * 
     * @param string $code
     * @param bool $plainText
     * @return string|null
     */
    public static function getActiveText($code, $plainText = false)
    {
        $version = static::getActiveVersion($code);
        
        if (!$version) {
            return null;
        }
        
        return $plainText ? $version['TEXT_PLAIN'] : $version['TEXT_HTML'];
    }
}
