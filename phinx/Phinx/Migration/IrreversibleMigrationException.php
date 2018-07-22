<?php

namespace Phinx\Migration;

/**
 * Exception class thrown when migrations cannot be reversed using the 'change'
 * feature.
 * @author Rob Morgan <robbym@gmail.com>
 */
class IrreversibleMigrationException extends \Exception
{
}
