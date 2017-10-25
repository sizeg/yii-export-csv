<?php

namespace application\extensions;

use CApplicationComponent;
use CDataProviderIterator;
use Closure;
use CWebLogRoute;
use IDataProvider;
use LogicException;
use Yii;

/**
 * DataProviderCsvExport
 *
 * @author SiZE <sizemail@gmail.com>
 */
class DataProviderCsvExport extends CApplicationComponent
{
    /**
     * @var IDataProvider 
     */
    private $dataProvider;
    
    /**
     * @var string Filename template
     */
    private $filename = 'grid_{date}.csv';
    
    /**
     * @var [] List of columns
     */
    private $columns = [];
    
    /**
     * @var [] List of column headers
     */
    private $columnHeaders = [];
    
    /**
     * @var string CSV delimiter
     */
    private $delimiter = ';';
    
    /**
     * @param IDataProvider $dataProvider
     */
    public function __construct(IDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }
    
    /**
     * @param string $filename
     * @return static
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getFilename()
    {
        return strtr($this->filename, ['{date}' => date('Ymd')]);
    }
    
    /**
     * @param [] $headers
     * @return static
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }
    
    /**
     * @param [] $headers
     * @return static
     */
    public function setColumnHeaders($headers)
    {
        $this->columnHeaders = $headers;

        return $this;
    }
    
    /**
     * @param string $delimiter
     * @return static
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }
    
    /**
     * @param string $string
     * @return string
     */
    private function convertEncoding($string)
    {
        return mb_convert_encoding($string, 'windows-1251', 'utf-8');
    }

    /**
     * Выполнить экспорт
     * @param bool $end
     * @throws LogicException
     */
    public function run($end = true)
    {
        $iterator = new CDataProviderIterator($this->dataProvider, 100);
        if (!$iterator->getTotalItemCount()) {
            throw new LogicException('No data to export');
        }
        
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $this->getFilename() . '"');
        
        $fp = fopen( 'php://output', 'w' );
        
        // Headers
        $rows = [];
        foreach ($this->columnHeaders as $header) {
            $rows[] = $this->convertEncoding($header);
        }
        fputcsv($fp, $rows, $this->delimiter);
        unset($rows);
        
        // Content
        foreach ($iterator as $item) {
            $row = [];
            foreach ($this->columns as $column) {
                if ($column instanceof Closure || is_callable($column)) {
                    $row[] = $this->convertEncoding(call_user_func($column, $this, $item));
                } else {
                    $row[] = $this->convertEncoding($item->$column);
                }
            }
            fputcsv($fp, $row, $this->delimiter);
        }
        
        fclose($fp);
        
        if ($end === true) {
            foreach (Yii::app()->log->routes as $route) {
                if ($route instanceof CWebLogRoute) {
                    $route->enabled = false; // disable any weblogroutes
                }
            }

            Yii::app()->end();
        }
    }
}
