<?php
namespace Tricon\HelloProduct\Model\Resource;
class Example extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
    $this->_init('rahul_magento_poc_table', 'Id');   //here Id is the primary key of custom table
    }
}