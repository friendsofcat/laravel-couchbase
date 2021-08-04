<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

class ArgsParametersTest extends TestCase {

    /**
     * @group ArgsParametersTest
     * @group ParametersTest
     */
    public function testParameters() {
        $query = DB::table('table6')->select();

        $this->assertEquals(false, DB::hasInlineParameters());
        $this->assertEquals('select `' . $query->from . '`.*, meta(`' . $query->from . '`).`id` as `_id` from `' . $query->from . '` where `table` = ?',
            $query->toSql());
    }
}
