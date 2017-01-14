<?php
/**
 * This file is part of MultiORM.
 *
 * MultiORM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MultiORM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with MultiORM. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   MultiORM
 * @author    Dan Dart
 * @copyright 2016 MultiORM
 * @license   http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version   git
 * @link      https://github.com/dandart/multiorm
**/
namespace MultiORM\Dao\Sql;

use MultiORM\Dao\DaoInterface;
use MultiORM\Model\Field\Hash;
use MultiORM\Model\Field\Field;
use MultiORM\Model\Field\Readonly;
use MultiORM\Model\Field\FieldId;
use MultiORM\Model\Field\Collection;
use MultiORM\Dao\Sql\Exception\NoAdapterException;

abstract class SqlAbstract implements DaoInterface
{
    const DATETIME_SQL = 'Y-m-d H:i:s';

    private static $_zendDb;

    public function __construct($configSettings)
    {
        self::$_zendDb = Zend_Db::factory($configSettings);
    }

    public static function setAdapter(\Zend_Db_Adapter_Abstract $zendDb)
    {
        self::$_zendDb = $zendDb;
    }

    protected function _getAdapter()
    {
        if(!self::$_zendDb instanceof \Zend_Db_Adapter_Abstract) {
            throw new NoAdapterException();
        }
        return self::$_zendDb;
    }

    abstract protected function _getTable();

    abstract protected function _getPrimaryKey();

    abstract public function convertToModel($arrData);

    protected function _modelToSql(Array $arrModel)
    {
        return $arrModel;
    }

    protected function _sqlToModel(Array $arrSql)
    {
        return $arrSql;
    }

    private function _textToSafe($strText)
    {
        if('UTF-8' != mb_detect_encoding($strText)) {
            $strText = mb_convert_encoding($strText, 'UTF-8');
        }
        return $strText;
    }

    protected function _timestampToSqlDateTime($intTimestamp)
    {
        $dt = DateTime::createFromFormat('U', $intTimestamp);
        return $dt->format(self::DATETIME_SQL);
    }

    protected function _sqlDateTimeToTimestamp($strDateTime)
    {
        $dt = DateTime::createFromFormat(self::DATETIME_SQL, $strDateTime);
        return $dt->getTimestamp();
    }

    protected function _save(Hash $hash)
    {
        $strTable = $this->_getTable();
        $collFields = $hash->getFields($this);
        $arrUpdate = $this->_getUpdateArray($collFields);
        if ($hash->bIsNew()) {
            $this->_getAdapter()->insert($strTable, $arrUpdate);
        } else {
            $strWhere = $this->_getAdapter()->quoteInto($this->_getPrimaryKey().' = ?', $hash->getId());
            $this->_getAdapter()->update($strTable, $arrUpdate, $strWhere);
        }
    }

    protected function _deleteWhere($strField, $strValue)
    {
        $strWhere = $this->_getAdapter()->quoteInto($strField.' = ?', $strValue);
        $this->_getAdapter()->delete($this->_getTable(), $strWhere);
    }

    protected function _delete(Hash $hash)
    {
        return $this->_deleteById($hash->getId());
    }

    protected function _deleteById($strId)
    {
        return $this->_deleteWhere($this->_getPrimaryKey(), $strId);
    }

    private function _getUpdateArray(Array $collFields)
    {
        $arrUpdate = array();
        foreach($collFields as $strFieldName => $objField) {
            if($objField->bIsDirty()) {
                $strClass = get_class($objField);
                switch($strClass) {
                case Field::class:
                case FieldId::class:
                    $arrUpdate[$strFieldName] = $this->_textToSafe($objField->getValue(null));
                    break;
                case Readonly::class:
                    break;
                default:
                    throw new Exception('Unmanaged class: '.$strClass);
                }
            }
        }
        return $this->_modelToSql($arrUpdate);
    }
}
