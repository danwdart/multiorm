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
 * @package    MultiORM
 * @author     Dan Dart
 * @copyright  2013 MultiORM
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version    git
 * @link       https://github.com/dandart/multiorm
**/
namespace MultiORM\Model\Field;

use Exception;

class Readonly extends FieldAbstract
{
    private $_mixedValue;

    public function setFromData($mixedValue)
    {
        $this->_mixedValue = $mixedValue;
    }

    public function setValue($mixedValue)
    {
        throw new Exception('Access violation: tried to write to a read only field');
    }

    public function getValue($mixedDefault)
    {
        return (is_null($this->_mixedValue))?
            $mixedDefault:
            $this->_mixedValue;
    }
}
