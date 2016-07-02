<?php
/**
 * File Dataset.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze;

/**
 * Class DataSet
 * @package Cze
 */
class Dataset implements \SeekableIterator, \Countable, \ArrayAccess
{
    const SELECT_WITH_FROM_PART = true;
    const SELECT_WITHOUT_FROM_PART = false;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    private $slaveAdapter = null;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    private $masterAdapter = null;

    /**
     * @var bool
     */
    protected $useSlaveInNextQuery = false;

    /**
     * @var bool
     */
    protected $useSlaveEnabled = false;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $tableName = '';

    /**
     * @var string
     */
    private $primaryKey = 'id';

    /**
     * @var int
     */
    private $pointer = 0;

    /**
     * @var int
     */
    private $count = -1;

    /**
     * @var bool
     */
    private $needsCreatedAt = false;

    /**
     * @var bool
     */
    private $needsUpdatedAt = false;

    /**
     * Constructor
     *
     * @param mixed $tableName TableName to use
     * @param mixed $id An optional rowId to read
     * @param mixed $key An optional keyField name
     * @param bool $needsCreatedAt has the table a created_at column and should be inserted in INSERTs if missing
     * @param bool $needsUpdatedAt has the table a updated_at column and should be inserted in UPDATEs if missing
     * @param bool $useSlaveEnabled
     */
    public function __construct(
        $tableName = '',
        $id = null,
        $key = 'id',
        $needsCreatedAt = false,
        $needsUpdatedAt = false,
        $useSlaveEnabled = false
    )
    {
        $this->connect();

        if ($key == 'id') {
            $key = 'id_' . $tableName;
        }

        $this->tableName = $tableName;
        $this->primaryKey = $key;
        $this->needsCreatedAt = $needsCreatedAt;
        $this->needsUpdatedAt = $needsUpdatedAt;
        $this->useSlaveEnabled = (bool)$useSlaveEnabled;
        $this->reset();

        if ((null !== $id) && (0 !== $id)) {
            $this->readRow($id);
        }
    }

    /**
     * Creates a new DataSet
     * Clears the internal data structure, flushing any existing data
     *
     * @return Dataset
     */
    public function create()
    {
        $this->data = [];
        return $this;
    }

    /**
     * Starts a database transaction
     *
     * @return Dataset
     */
    public function beginTransaction()
    {
        $this->getWriteAdapter()->beginTransaction();
        return $this;
    }

    /**
     * Commits the current transaction
     *
     * @return Dataset
     */
    public function commit()
    {
        $this->getWriteAdapter()->commit();

        return $this;
    }


    /**
     * Set flag to use slave DB in the next query
     *
     * @return $this
     */
    public function useSlaveInNextQuery()
    {
        $this->useSlaveInNextQuery = true && $this->useSlaveEnabled;

        return $this;
    }

    /**
     * Cancel the current transaction
     *
     * @return void
     */
    public function rollBack()
    {
        $this->getWriteAdapter()->rollBack();
    }

    /**
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->getWriteAdapter()->getConnection()->inTransaction();
    }

    /**
     * @param $sql
     * @return \PDOStatement|\Zend_Db_Statement
     */
    public function prepare($sql)
    {
        return $this->getWriteAdapter()->prepare($sql);
    }

    /**
     * Returns the current table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set the TableName value
     *
     * @param string $name
     * @return Dataset
     */
    public function setTableName($name)
    {
        $this->tableName = $name;
        return $this;
    }

    /**
     * Sets the primary key field name
     *
     * @param string $fieldName
     * @return Dataset
     */
    public function setPrimaryKey($fieldName)
    {
        $this->primaryKey = $fieldName;
        return $this;
    }

    /**
     * Gets primary key
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Reset the count and pointer values
     *
     * @return Dataset
     */
    protected function reset()
    {
        $this->count = count($this->data);
        if ($this->count > 0) {
            $this->pointer = 0;
        } else {
            $this->pointer = -1;
        }
        return $this;
    }

    /**
     * Read a single row from db into the dataset
     *
     * @param int|string|null $id The id of the record to be read
     * @param array $columns Optional array of column names to be read
     * @param string $keyField The name of the key field to search
     * @return boolean
     */
    public function readRow($id = null, array $columns = [], $keyField = '')
    {

        $this->data = [];

        if ($keyField == '') {
            $keyField = $this->primaryKey;
        }
        $columns = count($columns) > 0 ? implode(',', $columns) : '*';
        $this->data = $this->getReadAdapter()->fetchAll(
            'SELECT '
            . $columns
            . ' FROM '
            . $this->getEscapedTableName()
            . ' WHERE '
            . $keyField
            . '= ?',
            [$id]
        );
        $this->reset();

        return ($this->count > 0);
    }

    /**
     * Performs an update with a where clause
     * Returns true if rows were affected
     *
     *
     * @param array $args
     * @param array|string $whereParameters Where expressions, eg. 'field=?' => $value
     * @return bool
     * @throws Exception
     */
    public function writeWhere(array $args, $whereParameters = [])
    {
        if (empty($args)) {
            // cannot update data without given args
            return false;
        }

        $where = '';
        if (is_array($whereParameters)) {
            $i = count($whereParameters);

            foreach ($whereParameters as $field => $value) {
                $i--;
                $where .= $this->getWriteAdapter()->quoteInto($field, $value);
                if ($i > 0) {
                    $where .= ' AND ';
                }
            }
        } elseif (is_string($whereParameters)) {
            $where = $whereParameters;
        } else {
            throw new Exception($this, 'Where clause has invalid type');
        }

        if ($this->needsUpdatedAt) {
            $args['updated_at'] = Utils::sqlNowTime();
        }

        return ($this->getWriteAdapter()->update($this->tableName, $args, $where) >= 1);
    }

    /**
     * Read rows from db with a specific WHERE clause into the dataset
     *
     * $where can be:
     * array - key/value pairs of fieldnames/values to be AND'ed
     * string - a custom SQL WHERE clause
     *
     * @param array|string $parameters
     * @param array $columns
     * @throws Exception
     * @return boolean
     */
    public function readWhere($parameters = [], array $columns = [])
    {

        $this->data = [];

        if (is_array($parameters)) {
            $i = count($parameters);
            $where = ' WHERE ';

            foreach ($parameters as $field => $value) {
                $i--;
                $where .= $this->quoteInto($field, $value);
                if ($i > 0) {
                    $where .= ' AND ';
                }
            }
        } elseif (is_string($parameters)) {
            $where = ' WHERE ' . $parameters;
        } else {
            throw new Exception($this, 'Where clause has invalid type');
        }

        $columns = count($columns) > 0 ? implode(',', $columns) : '*';
        $this->data = $this->getReadAdapter()->fetchAll(
            'SELECT '
            . $columns
            . ' FROM '
            . $this->getEscapedTableName()
            . $where
        );
        $this->reset();

        return $this->count > 0;
    }

    /**
     * Opens a dataset with the specified query string, or performs a SELECT * if no query string is specified
     *
     * @param string|\Zend_Db_Select|null $query
     * @param array|null $parameters
     * @throws Exception
     * @return boolean
     */
    public function open($query = null, $parameters = null)
    {

        $this->data = [];
        if (null === $query) {
            $query = 'SELECT * FROM ' . $this->getEscapedTableName();
        } else {
            $query = $this->assembleSql($query);
        }

        try {
            if (null === $parameters) {
                $this->data = $this->getReadAdapter()->fetchAll($query);
            } else {
                $this->data = $this->getReadAdapter()->fetchAll($query, $parameters);
            }
        } catch (Exception $e) {
            throw new Exception($this, $e->getMessage());
        }
        $this->reset();

        return $this->count > 0;
    }

    /**
     * Write $args list into the record identified by id
     *
     * @param array $args field name/values to be written
     * @param mixed $id The id of the record, null or zero to create new
     * @param string $keyField The name of the id field
     * @return boolean
     */
    public function write($args = [], $id = null, $keyField = '')
    {
        $sqlNowTime = null;
        if ('' === $keyField) {
            $keyField = $this->primaryKey;
        }

        if ($this->needsUpdatedAt && !isset($args['updated_at'])) {
            $sqlNowTime = Utils::sqlNowTime();
            $args['updated_at'] = $sqlNowTime;
        }

        if ((null != $id) && (0 !== $id)) {
            $result = $this->getWriteAdapter()->update(
                $this->tableName,
                $args,
                "$keyField=" . $this->getWriteAdapter()->quote($id)
            ) == 1;
        } else {
            if ($this->needsCreatedAt && !isset($args['created_at'])) {
                $args['created_at'] = is_null($sqlNowTime) ? Utils::sqlNowTime() : $sqlNowTime;
            }

            $result = ($this->getWriteAdapter()->insert($this->tableName, $args) == 1);
        }

        return $result;
    }

    /**
     * @param array $data Array of table rows
     * @return int|null
     */
    public function bulkWrite(array $data)
    {
        $sqlNowTime = Utils::sqlNowTime();

        $sql = '';
        foreach ($data as $row) {
            if ($this->needsUpdatedAt) {
                $row['updated_at'] = $sqlNowTime;
            }
            if ($this->needsCreatedAt && !isset($row['created_at'])) {
                $row['created_at'] = $sqlNowTime;
            }

            if (empty($sql)) {
                $sql = $this->createInsertStatement($row);
            } else {
                $sql .= ',';
            }

            $sql .= $this->quotedValues($row);
        }

        if (!empty($sql)) {
            $result = $this->exec($sql);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @param mixed[] $row
     * @return string
     */
    private function createInsertStatement(array $row)
    {
        $tableName = $this->quoteIdentifier($this->tableName, true);
        $cols = array_keys($row);
        foreach ($cols as $key => $col) {
            $cols[$key] = $this->quoteIdentifier($col, true);
        }

        return 'INSERT INTO ' . $tableName . ' (' . implode(',', $cols) . ') VALUES ';
    }

    /**
     * @param mixed[] $row
     * @return string
     */
    public function quotedValues(array $row)
    {
        foreach ($row as $key => $val) {
            //get SQL-safe quoted value
            $row[$key] = (null === $val) ? 'null' : $this->quote($val);
        }

        return '(' . implode(',', array_values($row)) . ')';
    }

    /**
     *Returns if the supplied id exists inside the current table
     *
     * @param mixed $id The id to search
     * @param string $keyField The name of the field to search
     * @return bool
     */
    public function isValidId($id, $keyField = '')
    {

        if ($keyField == '') {
            $keyField = $this->primaryKey;
        }

        $tmp = $this->getReadAdapter()->fetchAll(
            'SELECT ' . $keyField
            . ' FROM ' . $this->getEscapedTableName()
            . ' WHERE ' . $keyField . '= ?',
            [$id]
        );
        return (!empty($tmp));
    }

    /**
     * Returns the highest id value for the given field or the default primary key field
     *
     * @param string $keyField The name of the field to search
     * @return mixed
     */
    public function getMaxId($keyField = '')
    {

        if ($keyField == '') {
            $keyField = $this->primaryKey;
        }
        $tmp = $this->getReadAdapter()->fetchRow(
            'SELECT MAX(' . $keyField . ') AS id FROM ' . $this->getEscapedTableName()
        );

        return isset($tmp['id']) ? $tmp['id'] : 0;
    }

    /**
     * Returns the last inserted id
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->getWriteAdapter()->lastInsertId();
    }

    /**
     * Returns the number of rows in the dataset table,
     * optionally accepting a where clause
     *
     * @param string $whereClause an optional SQL where clause
     * @return int
     */
    public function queryRowCount($whereClause = '')
    {
        if ('' == $whereClause) {
            $tmp = $this->getReadAdapter()->fetchRow(
                'SELECT COUNT(*) AS cnt FROM ' . $this->getEscapedTableName()
            );
        } else {
            $tmp = $this->getReadAdapter()->fetchRow(
                'SELECT COUNT(*) AS cnt FROM ' . $this->getEscapedTableName() . ' WHERE ' . $whereClause
            );
        }

        return (isset($tmp['cnt'])) ? $tmp['cnt'] : 0;
    }

    /**
     * fetchAll wrapper
     * This function does not invalidate/store any data on the dataset; All read rows are returned as an array directly
     *
     * @param string|\Zend_Db_Select|null $sql
     * @param array|null $params
     * @return array
     */
    public function fetchAll($sql = null, array $params = [])
    {
        $sql = $this->assembleSelectSql($sql);

        return $this->getReadAdapter()->fetchAll($sql, $params);
    }

    /**
     * fetchAssoc wrapper
     *
     * @param string|\Zend_Db_Select|null $sql
     * @param array $params
     * @return array keys are the first column in select statement, values are array contains all columns in select
     */
    public function fetchAssoc($sql = null, array $params = [])
    {
        $sql = $this->assembleSelectSql($sql);

        return $this->getReadAdapter()->fetchAssoc($sql, $params);
    }

    /**
     * fetchRow wrapper
     * This function does not invalidate/store any data on the dataset; All read rows are returned as an array directly
     *
     * @param string|\Zend_Db_Select $sql
     * @param array $params
     * @return mixed Array, object, or scalar depending on fetch mode.
     */
    public function fetchRow($sql, array $params = [])
    {
        if ($sql instanceof \Zend_Db_Select) {
            $sql->limit(1);
        }

        $sql = $this->assembleSql($sql);

        return $this->getReadAdapter()->fetchRow($sql, $params);
    }

    /**
     * Populates Dataset data with results, can be iterated
     * This method does not return nothing, but populates model class to have row information
     *
     * @param string|\Zend_Db_Select $sql
     * @param array $params
     */
    public function populate($sql, array $params = [])
    {
        $sql = $this->assembleSql($sql);
        $this->data = $this->getReadAdapter()->fetchAll($sql);
        $this->reset();
    }

    /**
     * Delegate to read adapter.
     *
     * @param $select
     * @param null $bind
     * @return array
     */
    public function fetchPairs($select, $bind = null)
    {
        return $this->getReadAdapter()->fetchPairs($select, $bind);
    }

    /**
     * @param $select
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return array
     * @see \Zend_Db_Adapter_Abstract::fetchCol()
     */
    public function fetchCol($select, $bind = [])
    {
        return $this->getReadAdapter()->fetchCol($select, $bind);
    }

    /**
     * @param $select
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return mixed|false
     * @see \Zend_Db_Adapter_Abstract::fetchOne()
     */
    public function fetchOne($select, $bind = [])
    {
        return $this->getReadAdapter()->fetchOne($select, $bind);
    }

    /**
     * @param \Zend_Db_Select $select
     * @param array $params
     * @return \Generator
     * @throws Exception
     */
    public function fetchEach(\Zend_Db_Select $select, $params = [])
    {
        $sql = $select->assemble();
        $statement = $this->getReadAdapter()->query($sql, $params);
        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * @param string|\Zend_Db_Select $sql
     * @return mixed|null|string
     * @throws Exception
     */
    private function assembleSelectSql($sql)
    {
        if (null === $sql) {
            $sql = 'SELECT * FROM ' . $this->getEscapedTableName();
        } else {
            $sql = $this->assembleSql($sql);
        }

        return $sql;
    }

    /**
     * exec wrapper
     *
     * @param string|\Zend_Db_Select $sql
     * @return integer
     */
    public function exec($sql)
    {
        $sql = $this->assembleSql($sql);
        return $this->getWriteAdapter()->exec($sql);
    }

    /**
     * Returns the given field value, for the current row
     *
     * @param mixed $field
     * @param mixed $emptyValue
     * @return mixed|null
     */
    public function get($field, $emptyValue = null)
    {
        if ($this->valid()) {
            $row = $this->data[$this->pointer];
            return (!isset($row[$field])) ? (empty($row[$field]) ? $emptyValue : $row[$field]) : $row[$field];
        } else {
            return $emptyValue;
        }
    }

    /**
     * Returns the complete Dataset contents
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Removes the row identified by id
     *
     * @param mixed $id The id of the record to delete
     * @param string $keyField The name of the id field
     * @return bool
     */
    public function delete($id, $keyField = '')
    {
        if ('' == $keyField) {
            $keyField = $this->primaryKey;
        }

        return (
            $this->getWriteAdapter()->delete(
                $this->tableName,
                $keyField . '=' . $this->getWriteAdapter()->quote($id)
            ) > 0
        );
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
        $fields = [];
        if (is_array($where)) {
            foreach ($where as $field => $value) {
                if ($value === null) {
                    $fields[] = $field . " IS NULL";
                } else {
                    $fields[] = $field . " = " . $this->getWriteAdapter()->quote($value);
                }
            }
            $where = implode(' AND ', $fields);
        } elseif (!is_string($where)) {
            throw new Exception($this, 'Where has invalid type');
        }

        return ($this->getWriteAdapter()->delete($this->tableName, $where) > 0);
    }

    /**
     * Removes rows using a specified WHERE clause with argument substitution
     *
     * @param string[] $where key-value pairs of removal criteria to be AND'ed
     * @return bool
     *
     * @throws Exception
     */
    public function deleteWhereQuoted(array $where)
    {
        $fields = [];
        foreach ($where as $field => $value) {
            $fields[] = $this->getWriteAdapter()->quoteInto($field, $value);
        }
        $where = implode(' AND ', $fields);

        return ($this->getWriteAdapter()->delete($this->tableName, $where) > 0);
    }

    /**
     * Returns true if a record matching the given arguments and with id<>$idToSkip exists
     *
     * @param array $args
     * @param integer $idToSkip
     * @param string $keyField
     * @return boolean
     */
    public function keyExists($args, $idToSkip, $keyField = '')
    {
        if ('' == $keyField) {
            $keyField = $this->primaryKey;
        }

        $values = [];
        $fields = '';
        foreach ($args as $field => $value) {
            $fields[] = $field . '=? ';
            $values[] = $value;
        }

        // if more then one has to be connected with AND
        $fields = implode(' AND ', $fields);

        $data = $this->getReadAdapter()->fetchRow(
            'SELECT ' . $keyField . ' FROM '
            . $this->getEscapedTableName()
            . ' WHERE '
            . $fields
            . ' AND '
            . $keyField . '<>'
            . $idToSkip,
            $values
        );
        return isset($data[$keyField]);
    }

    /**
     * Take the Iterator to position $position
     *
     * @param int $position the position to seek to
     * @return Dataset
     * @throws Exception
     */
    public function seek($position)
    {
        $position = (int)$position;
        if ($position < 0 || $position >= $this->count) {
            throw new Exception($this, "Illegal index $position");
        }
        $this->pointer = $position;
        return $this;
    }

    /**
     * Return the current Row
     * Required by interface Iterator.
     *
     * @return array|null
     */
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        return $this->data[$this->pointer];
    }

    /**
     * Return the identifying key of the current element, used for array-like iterations
     *
     * @return int
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Move forward to next element
     *
     * @return Dataset
     */
    public function next()
    {
        ++$this->pointer;
        return $this;
    }

    /**
     * Rewind the RowSet iterator to the first element
     *
     * @return Dataset
     */
    public function rewind()
    {
        $this->pointer = 0;
        return $this;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->pointer >= 0 && $this->pointer < $this->count;
    }

    /**
     * Returns the number of elements in the dataset.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Returns a row from a known position
     *
     * @param int $position the position of the row expected
     * @param bool $seek whether or not seek the iterator to that position after
     * @return array|null
     * @throws Exception
     */
    public function getRow($position, $seek = false)
    {
        $key = $this->key();
        try {
            $this->seek($position);
            $row = $this->current();
        } catch (Exception $e) {
            throw new Exception(
                $this,
                'No row could be found at position ' . (int) $position
            );
        }

        if ($seek === false) {
            $this->seek($key);
        }

        return $row;
    }

    /**
     * Check if an offset exists
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->data[(int) $offset]);
    }

    /**
     * Get the row for the given offset
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @throws Exception
     * @return array
     */
    public function offsetGet($offset)
    {
        $offset = (int) $offset;

        if ($offset < 0 || $offset >= $this->count) {
            throw new Exception($this, "Illegal index $offset. Maybe data was not read?");
        }
        $this->pointer = $offset;

        return $this->current();
    }

    /**
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * Returns all rows as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Returns data fields to be serialized
     *
     * @return array
     */
    public function __sleep()
    {
        // The _db adapter is never serialized, but re-connected on wakeup()
        return ['data', 'tableName', 'primaryKey', 'pointer', 'count'];
    }

    /**
     * Code to execute  upon wakeup
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->connect();
    }

    /**
     * Connects to Database
     */
    protected function connect()
    {
        $adapters = Application::getDb();
        $this->slaveAdapter = $adapters['reader'];
        $this->masterAdapter = $adapters['writer'];

    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getReadAdapter()
    {
        if ($this->useSlaveInNextQuery === true && !$this->inTransaction()) {
            $this->useSlaveInNextQuery = false;
            return $this->slaveAdapter;
        } else {
            $this->useSlaveInNextQuery = false;
            return $this->masterAdapter;
        }
    }

    /**
     * @return \Zend_Db_Adapter_Abstract|\Zend_Db_Adapter_Pdo_Abstract
     */
    public function getWriteAdapter()
    {
        return $this->masterAdapter;
    }

    /**
     * Returns an instance of a Zend_Db_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     *
     * @return \Zend_Db_Select
     */
    public function select($withFromPart = self::SELECT_WITH_FROM_PART)
    {
        $select = new \Zend_Db_Select($this->getReadAdapter());
        if ($withFromPart == self::SELECT_WITH_FROM_PART) {
            $select->from($this->tableName, \Zend_Db_Select::SQL_WILDCARD);
        }

        return $select;
    }

    /**
     * Returns an instance of a Zend_Db_Select object.
     *
     * @param array $columns Array of db columns to use
     *
     * @return \Zend_Db_Select
     */
    public function selectColumns(array $columns)
    {
        $select = new \Zend_Db_Select($this->getReadAdapter());
        return $select->from($this->tableName, $columns);
    }

    /**
     *
     * @param string $text The text with a placeholder.
     * @param mixed $value The value to quote.
     * @param string|null $type OPTIONAL SQL datatype
     * @param integer|null $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        return $this->getReadAdapter()->quoteInto($text, $value, $type, $count);
    }

    /**
     * @see \Zend_Db_Adapter_Abstract::quote()
     * @param mixed $value The value to quote.
     * @param mixed $type OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        return $this->getReadAdapter()->quote($value, $type);
    }

    /**
     * @see Zend_Db_Adapter_Abstract::quoteIdentifier()
     * @param mixed $value The value to quote.
     * @param mixed $type OPTIONAL the SQL datatype name, or constant, or null.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($value, $type = null)
    {
        return $this->getReadAdapter()->quoteIdentifier($value, $type);
    }

    /**
     * Checks/assembles sql
     *
     * @param string|\Zend_Db_Select $sql
     * @return mixed|null|string
     * @throws Exception
     */
    protected function assembleSql($sql)
    {
        if (is_string($sql)) {
            $sql = str_replace(
                ['__tableName__', '__key__'],
                [$this->tableName, $this->primaryKey],
                $sql
            );
        } elseif ($sql instanceof \Zend_Db_Select) {
            $sql = $sql->assemble();
        } else {
            throw new Exception($this, 'Sql parameter is neither a string or instance of Zend_Db_Select');
        }

        return $sql;
    }

    /**
     * Returns the escaped version of the tableName
     *
     * @return mixed|string
     */
    protected function getEscapedTableName()
    {
        if ($this->getReadAdapter() instanceof \Zend_Db_Adapter_Pdo_Mysql) {
            return "`{$this->tableName}`";
        }

        return $this->tableName;
    }
}
