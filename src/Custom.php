<?php

namespace Zls\Migration;

use Z;
use Zls\Migration\AbstractMigration as M;

class Custom extends M
{
    public function up($table, array $data, $comment = '', $processing = null)
    {
        $obj = $this->table($table);
        $obj->comment($comment);
        foreach ($data as $k => $d) {
            $operation = Z::arrayGet($d, 'operation', 'addColumn');
            $type = Z::arrayGet($d, 'type', 'string');
            $comment = Z::arrayGet($d, 'comment', is_string($d) ? $d : '');
            $options = Z::arrayGet($d, 'options', []);
            if ($comment) {
                $options['comment'] = $comment;
            }
            $obj->$operation($k, $type, $options);
        }
        if ($processing instanceof \Closure){
            $processing($obj);
            return;
        }
            
        $obj->save();
    }
}
