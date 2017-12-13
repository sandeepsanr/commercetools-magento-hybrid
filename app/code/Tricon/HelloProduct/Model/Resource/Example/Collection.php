<?php
namespace Tricon\HelloProduct\Model\Resource\Example;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
    $this->_init(
        'Tricon\HelloProduct\Model\Example',
        'Tricon\HelloProduct\Model\Resource\Example'
    );

    }
}