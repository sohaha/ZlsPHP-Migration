<?php

namespace Zls\Migration;

use Phinx\Migration\AbstractMigration as PhinxAbstractMigration;

/**
 * Zls
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v0.0.2
 * @updatetime    2018-07-17 15:31
 */
class AbstractMigration extends PhinxAbstractMigration
{
    const TYPE_BIGINTEGER = 'biginteger';
    const TYPE_BINARY = 'binary';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_FLOAT = 'float';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_TIME = 'time';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_MYSQL_ENUM = 'enum';
    const TYPE_MYSQL_SET = 'set';
    const TYPE_MYSQL_BLOB = 'blob';
    const TYPE_MYSQL_JSON = 'json';
    const TYPE_POSTGRES_SMALLINT = 'smallint';
    const TYPE_POSTGRES_JSON = 'json';
    const TYPE_POSTGRES_JSONB = 'jsonb';
    const TYPE_POSTGRES_UUID = 'uuid';
    const TYPE_SQLSERVER_BIT = 'boolean';

    const OPTIONS_COMMENT = 'comment';
    const OPTIONS_AFTER = 'after';
    const OPTIONS_DEFAULT = 'default';
    const OPTIONS_LENGTH = 'length';
    const OPTIONS_LIMIT = 'limit';
    const OPTIONS_DECIMAL_PRECISION = 'precision';
    const OPTIONS_DECIMAL_SCALE = 'scale';
    const OPTIONS_DECIMAL_SIGNED = 'signed';
    const OPTIONS_NULL = 'null';
    const OPTIONS_BOOLEAN_SIGNED = 'signed';
    const OPTIONS_STRING_COLLATION = 'collation';
    const OPTIONS_STRING_ENCODING = 'encoding';
    const OPTIONS_MYSQL_ENUM_VALUES = 'values';
    const OPTIONS_MYSQL_SET_VALUES = 'values';
    const OPTIONS_BIGINTEGER_IDENTITY = 'identity';
    const OPTIONS_BIGINTEGER_SIGNED = 'signed';
    const OPTIONS_INTEGER_IDENTITY = 'identity';
    const OPTIONS_INTEGER_SIGNED = 'signed';
    const OPTIONS_TIMESTAMP_DEFAULT = 'default';
    const OPTIONS_TIMESTAMP_TIMEZONE = 'timezone';
    const OPTIONS_TIMESTAMP_UPDATE = 'update';
}
