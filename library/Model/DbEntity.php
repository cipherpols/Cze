<?php
/**
 * File DbEntity.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Model;

use Cze\AbstractModel;
use Cze\Application;
use Cze\Dataset;
use Cze\Exception;

/**
 * Class DbEntity
 * @package Cze\Model
 */
class DbEntity extends AbstractModel
{
    const NO_CREATED_AT = false;
    const NO_UPDATED_AT = false;
    const HAS_CREATED_AT = true;
    const HAS_UPDATED_AT = true;
    const NO_SPECIAL_PRIMARY_KEY = null;
    /**
     * @var Dataset
     */
    private $dataset = null;

    protected $tableName = '';
    protected $primaryKey = '';
    protected $needsCreatedAt = false; // saved for lazy loading dataset
    protected $needsUpdatedAt = false; // saved for lazy loading dataset

    /**
     * Constructor
     *
     * @param integer|mixed $id
     * @param string $tableName
     * @param string $primaryKey
     * @param bool $needsCreatedAt has the table a created_at column and should be inserted
     *                             in INSERTs if missing (use the *_CREATE_AT constants)
     * @param bool $needsUpdatedAt has the table a updated_at column and should be inserted
     *                             in UPDATEs if missing (use the *_UPDATED_AT constants)
     */
    public function __construct(
        $id = null,
        $tableName = '',
        $primaryKey = null,
        $needsCreatedAt = false,
        $needsUpdatedAt = false
    ) {
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
        $this->needsCreatedAt = $needsCreatedAt;
        $this->needsUpdatedAt = $needsUpdatedAt;
        if ($id) {
            $this->getDataset()->readRow($id);
        }
    }

    /**
     * Read Item
     * @param int $id
     * @return array|null
     */
    public function read($id)
    {
        $this->getDataset()->readRow($id);
        if (count($this->getDataset()) > 0) {
            return $this->getDataset()->current();
        } else {
            return [];
        }
    }

    /**
     * Read a parameter
     *
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = '')
    {
        if ($this->getDataset()->count() > 0) {
            return $this->getDataset()->get($name, $defaultValue);
        } else {
            return $defaultValue;
        }
    }

    /**
     * Save and returns the id
     *
     * @param array $data
     * @param integer $id
     * @return integer The primary key id of the inserted/updated data row
     */
    public function save($data = [], $id = null)
    {
        $this->getDataset()->write($data, $id);
        if ((int) $id == 0) {
            $id = (int) $this->getDataset()->lastInsertId();
        }
        return (int) $id;
    }

    /**
     * @param array $data Array of table rows
     * @return int|null
     */
    public function bulkSave($data = [])
    {
        $result = null;
        $rowCount = count($data);
        if ($rowCount === 1) {
            $result = $this->save($data[0]);
        } elseif ($rowCount > 1) {
            $result = $this->getDataset()->bulkWrite($data);
        }
        return $result;
    }

    /**
     * Returns the last inserted id
     *
     * @return integer
     */
    public function lastId()
    {
        return $this->getDataset()->lastInsertId();
    }

    /**
     * Returns the current entity table name
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Returns the current dataset
     * @return Dataset
     */
    public function getDataset()
    {
        if (null === $this->dataset) {
            $this->dataset = new Dataset(
                $this->tableName,
                null,
                'id',
                $this->needsCreatedAt,
                $this->needsUpdatedAt,
                (bool) Application::getConfig()->resources->multidb->slave->enabled
            );

            if (!empty($this->primaryKey)) {
                $this->dataset->setPrimaryKey($this->primaryKey);
            }
        }
        
        return $this->dataset;
    }

    /**
     * Removes rows using a specified WHERE clause
     *
     * @param array|string $where key-value pairs of removal criteria to be AND'ed, or a WHERE sql clause
     * @throws Exception
     * @return bool
     */
    public function deleteWhere($where = [])
    {
        return $this->getDataset()->deleteWhere($where);
    }

    /**
     * Removes an entry
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->getDataset()->delete($id);
    }

    /**
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getAllByField($field, $key)
    {
        return $this->getDataset()->fetchAll(
            $this->getDataset()
                ->select()
                ->where($field . ' = ?', $key)
        );
    }

    /**
     * @param string $field
     * @param array $values
     * @return array
     */
    public function getAllByFieldValues($field, $values)
    {
        return $this->getDataset()->fetchAll(
            $this->getDataset()
                ->select()
                ->where($field . ' IN (?)', $values)
        );
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->getDataset()->fetchAll(
            $this->getDataset()
                ->select()
        );
    }

    /**
     * Quotes all entries in $textToQuote and finally implode()s them with $glue.
     *
     * example:
     * $this->implodeQuoted(
     *       'AND',
     *       array(
     *          'user_group.fk_user_role = user.fk_user_role',
     *          'user_group.deleted = ?' => Cze_Model_User_Group::STATE_NOT_DELETED,
     *          ['user.deleted = ?' => Cze_Model_User::STATE_NOT_DELETED]
     *       )
     * );
     *
     * @param string $glue To put between entries of $textToQuote, padded with spaces, if not already
     * @param array $textToQuote
     *
     * @return string
     */
    protected function implodeQuoted($glue, array $textToQuote)
    {
        if (!empty($glue) && trim($glue) == $glue) {
            $glue = " {$glue} ";
        }
        foreach ($textToQuote as $text => &$value) {
            if (!is_numeric($text)) {
                $value = $this->getDataset()->quoteInto($text, $value);
            } elseif (is_array($value)) {
                foreach ($value as $innerText => $innerValue) {
                    $value[$innerText] = $this->getDataset()->quoteInto($innerText, $innerValue);
                }
                $value = implode($glue, $value);
            }
        };

        return implode($glue, $textToQuote);
    }

    /**
     * Returns a single row by queried by a param value
     * @param $paramName
     * @param $value
     * @return mixed
     */
    public function getRowByParam($paramName, $value)
    {
        $select = $this->getDataset()->select()
            ->where($paramName . ' = ?', $value);

        return $this->getDataset()->fetchRow($select);
    }

    /**
     * @param array $ids
     * @param array $fields
     * @return mixed[]
     */
    public function getFieldsByIds(array $ids, array $fields)
    {
        if (empty($ids)) {
            return [];
        }
        $select =
            $this->getDataset()->selectColumns($fields)
                ->where($this->getDataset()->getPrimaryKey() . ' IN (?)', $ids);

        return $this->getDataset()->fetchAll($select);
    }
}
