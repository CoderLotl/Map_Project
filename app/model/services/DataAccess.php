<?php

namespace Model\Services;

use Exception;
use PDO;
use Model\Utilities\Log;

class DataAccess
{
    public static $pdo;

    private static function Catch($e)
    {
        Log::WriteLog('db_errors.txt', $e->getMessage());
        return false;
    }

    public static function Count(string $table, $whereColumn = null, $whereValue = null)
    {
        try
        {            
            $statement = null;
            $query = '';
            if($whereColumn && $whereValue)
            {
                $query = "SELECT COUNT(*) FROM $table WHERE $whereColumn='$whereValue'";
            }
            else
            {
                $query = "SELECT COUNT(*) FROM $table";
            }

            $statement = self::$pdo->prepare($query);
            $statement->execute();
            return $statement->fetchColumn();
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    /**
     * Returns a group of entries equal to $groupsize. The number of the group will be equal to $selectedGroup.
     * 
     */
    public static function ReturnByGroups(string $table, int $selectedGroup, int $groupSize, string $orderByColumn = null, bool $desc = true, $whereColumn = null, $whereValue = null)
    {
        $offset = ($selectedGroup - 1) * $groupSize;
        $order = '';
        if($orderByColumn)
        {
            $order = "ORDER BY {$orderByColumn}";
            if($desc)
            {
                $order .= " DESC";
            }
        }

        try
        {
            $query = '';
            if($whereColumn && $whereValue)
            {
                $query = "SELECT * FROM $table WHERE $whereColumn='$whereValue' $order LIMIT $groupSize OFFSET $offset";
            }
            else
            {
                $query = "SELECT * FROM $table $order LIMIT $groupSize OFFSET $offset";
            }
            $statement = self::$pdo->prepare($query);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function ReturnByGroupsJSONSearch(string $table, int $selectedGroup, int $groupSize, $whereColumn, $whereValue, $whereProperty, string $orderByColumn = null, bool $desc = true)
    {
        $offset = ($selectedGroup - 1) * $groupSize;
        $order = '';
        if($orderByColumn)
        {
            $order = "ORDER BY {$orderByColumn}";
            if($desc)
            {
                $order .= " DESC";
            }
        }

        try
        {
            $query = "SELECT * 
            FROM $table 
            WHERE 
              json_extract($whereColumn, '$.$whereProperty') LIKE '%' || ? || '%' OR 
              json_extract($whereColumn, '$.$whereProperty') = ? OR 
              json_extract($whereColumn, '$.$whereProperty') = ?";            
            $statement = self::$pdo->prepare($query);
            $statement->execute([$whereValue, $whereValue, $whereValue]);
            
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    /////////////////////////////////////////////////////////////
    #region - - - [ BASIC FUNCTIONS ]
    public static function Select(string $table, string $column = null)
    {
        try
        {
            if(!$column)
            {
                $column = '*';
            }
            $statement = self::$pdo->prepare("SELECT $column FROM $table");
            $statement->execute();            
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function SelectDistinctColumn(string $table, string $column)
    {
        try
        {
            $statement = self::$pdo->prepare("SELECT DISTINCT $column FROM $table");
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_COLUMN);            
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function SelectWhere(string $table, array $whereColumn, array $whereValue,  array $columns = null)
    {
        try
        {
            $columnsClause = '';
            if(!$columns)
            {
                $columnsClause = '*';
            }
            else
            {
                $lastColumn = end($columns);
                foreach($columns as $col)
                {
                    $columnsClause .= "`{$col}`";
                    if($col != $lastColumn)
                    {
                        $columnsClause .= ', ';
                    }
                }
            }

            $whereClause = '';
            $lastColumn = end($whereColumn);
            for($i = 0; $i < count($whereColumn); $i++)
            {
                $whereClause .= "{$whereColumn[$i]} = '{$whereValue[$i]}'";
                if($whereColumn[$i] != $lastColumn)
                {
                    $whereClause .= ' AND ';
                }
            }

            $statement = self::$pdo->prepare("SELECT $columnsClause FROM $table WHERE $whereClause");
            $statement->execute();            
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function SelectLast(string $table, string $column)
    {
        try
        {
            $statement = self::$pdo->prepare("SELECT $column
            FROM $table
            ORDER BY $column DESC
            LIMIT 1;");
            $statement->execute();
            return $statement->fetchColumn();
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    /**
     *
        $query =    "SELECT {$queryColumn}
            FROM {$table1}
            JOIN {$table2} ON {$table1}.{$join1} = {$table2}.{$join2}
            WHERE {$table2}.{$whereColumn} = '{$whereValue}'
            ";
     */
    public static function SelectWithJoin(string $table1, string $table2, string $join1, string $join2, string $whereColumn, string $whereValue, $column = null, $noAssoc = null)
    {        
        try
        {
            if(!$column)
            {
                $queryColumn = "{$table1}.*";
            }
            else
            {
                $queryColumn = '';
                $lastElement = end($column);
                foreach($column as $col)
                {
                    $queryColumn .= "{$col}";
                    if($col != $lastElement)
                    {
                        $queryColumn .= ', ';
                    }
                    else
                    {
                        $queryColumn .= ' ';
                    }
                }                
            }
    
            $query =    "SELECT {$queryColumn}
                        FROM {$table1}
                        JOIN {$table2} ON {$table1}.{$join1} = {$table2}.{$join2}
                        WHERE {$table2}.{$whereColumn} = '{$whereValue}'
                        ";    
            $statement = self::$pdo->prepare($query);
            $statement->execute();
            if($noAssoc)
            {
                $result = $statement->fetch();
            }
            else
            {
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            }
    
            return $result;
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function Insert(string $table, array $columns, array $values)
    {
        try
        {
            $queryColumns = '`' . implode('`, `', $columns) . '`';
            $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
    
            $query = "INSERT INTO `{$table}` ({$queryColumns}) VALUES ({$placeholders})";
            $statement = self::$pdo->prepare($query);
    
            $statement->execute($values);
    
            return true;
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function Delete(string $table, $whereColumn, $whereValue)
    {
        try
        {
            $query = "DELETE FROM $table WHERE $whereColumn = '$whereValue'";            
            $statement = self::$pdo->prepare($query);
            $statement->execute();
            return $statement->rowCount() > 0;
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function GetID($table)
    {
        $id = 0;
        $lastID = DataAccess::SelectLast($table, 'id');
        
        if($lastID)
        {
            $id = $lastID;
        }
        return $id;
    }

    public static function Update(string $table, array $columns, array $values, string $whereColumn, $whereValue)
    {
        try
        {
            $setClause = '';
            $params = [];
            foreach($columns as $key => $col)
            {
                $setClause .= "`{$col}` = ?";
                $params[] = $values[$key];
                if($key < count($columns) - 1)
                {
                    $setClause .= ', ';
                }
            }
            $query = "UPDATE `{$table}` SET {$setClause} WHERE `{$whereColumn}` = ?";
            $params[] = $whereValue;
    
            $statement = self::$pdo->prepare($query);
            return $statement->execute($params);            
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function UpdateMultipleWhere(string $table, array $columns, array $values, array $whereColumn, array $whereValue)
    {
        try
        {
            $setClause = '';
            $lastColumn = end($columns);
            foreach($columns as $key => $col)
            {
                $setClause .= "`{$col}` = '{$values[$key]}'";
                if($col != $lastColumn)
                {
                    $setClause .= ', ';
                }
            }

            $whereClause = '';
            $lastColumn = end($whereColumn);
            for($i = 0; $i < count($whereColumn); $i++)
            {
                $whereClause .= "{$whereColumn[$i]} = '{$whereValue[$i]}'";
                
                if($whereColumn[$i] != $lastColumn)
                {
                    $whereClause .= ' AND ';
                }
            }

            $query = "UPDATE `{$table}` SET {$setClause} WHERE $whereClause";
            
            $statement = self::$pdo->prepare($query);

            return $statement->execute();
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }
    #endregion    
}