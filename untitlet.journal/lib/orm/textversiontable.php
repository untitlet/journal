<?php
namespace Untitlet\Journal\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;

class TextVersionTable extends DataManager
{
    public static function getTableName()
    {
        return 'b_untitlet_journal_text_version';
    }

    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            
            new StringField('CODE', [
                'size' => 64,
                'required' => true,
            ]),
            
            new IntegerField('VERSION', [
                'required' => true,
                'default_value' => 1,
            ]),
            
            new TextField('TEXT_HTML', [
                'required' => true,
            ]),
            
            new TextField('TEXT_PLAIN', [
                'required' => true,
            ]),
            
            new BooleanField('IS_ACTIVE', [
                'required' => true,
                'default_value' => false,
            ]),
            
            new DatetimeField('CREATED_AT', [
                'required' => true,
                'default_value' => new \Bitrix\Main\DB\SqlExpression('NOW()'),
            ]),
        ];
    }
    
    /**
     * Get active version by code
     */
    public static function getActiveVersion($code)
    {
        return static::getRow([
            'select' => ['*'],
            'filter' => [
                '=CODE' => $code,
                '=IS_ACTIVE' => true,
            ],
        ]);
    }
    
    /**
     * Create new version and deactivate old ones
     */
    public static function createVersion($code, $textHtml, $textPlain)
    {
        $connection = static::getConnection();
        
        // Deactivate all existing versions for this code
        $connection->query(
            "UPDATE " . static::getTableName() . " 
             SET IS_ACTIVE = '0' 
             WHERE CODE = ?",
            [$code]
        );
        
        // Get next version number
        $lastVersion = static::getRow([
            'select' => ['VERSION'],
            'filter' => ['=CODE' => $code],
            'order' => ['VERSION' => 'DESC'],
        ]);
        
        $nextVersion = $lastVersion ? (int)$lastVersion['VERSION'] + 1 : 1;
        
        // Create new active version
        return static::add([
            'CODE' => $code,
            'VERSION' => $nextVersion,
            'TEXT_HTML' => $textHtml,
            'TEXT_PLAIN' => $textPlain,
            'IS_ACTIVE' => true,
        ]);
    }
}
