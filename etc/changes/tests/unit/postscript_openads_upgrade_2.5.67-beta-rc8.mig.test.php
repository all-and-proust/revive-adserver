<?php
/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

// require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/etc/changes/tests/unit/MigrationTest.php';
// require_once MAX_PATH . '/lib/OA/Upgrade/Upgrade.php';

require_once MAX_PATH . '/etc/changes/postscript_openads_upgrade_2.5.67-beta-rc8.php';


/**
 * A class for testing creation and resetting of affiliates_affiliateid_seq
 * sequence.
 *
 * @package    MaxDal
 * @subpackage TestSuite
 *
 */
class Migration_postscript_2_5_67_RC8Test extends MigrationTest
{
    function testExecute()
    {
        if ($this->oDbh->dbsyntax == 'pgsql') {
            $prefix = $this->getPrefix();
            $this->initDatabase(581, array('affiliates'));

            $aAValues = array(
                array('name' => 'x'),
                array('name' => 'y')
            );
            foreach ($aAValues as $aValues) {
                $sql = OA_DB_Sql::sqlForInsert('affiliates', $aValues);
                $this->oDbh->exec($sql);
            }

            // Simulate upgrade from phpPgAds with a wrongly named sequence
            $sequenceName = "{$prefix}affiliates_affiliateid_seq";
            $this->oDbh->exec("ALTER TABLE {$sequenceName} RENAME TO {$prefix}foobar");
            $this->oDbh->exec("ALTER TABLE {$prefix}affiliates ALTER affiliateid SET DEFAULT nextval('{$prefix}foobar')");

            Mock::generatePartial(
                'OA_UpgradeLogger',
                $mockLogger = 'OA_UpgradeLogger'.rand(),
                array('logOnly', 'logError', 'log')
            );

            $oLogger = new $mockLogger($this);
            $oLogger->setReturnValue('logOnly', true);
            $oLogger->setReturnValue('logError', true);
            $oLogger->setReturnValue('log', true);

            $mockUpgrade = new StdClass();
            $mockUpgrade->oLogger = $oLogger;
            $mockUpgrade->oDBUpgrader = new OA_DB_Upgrade($oLogger);
            $mockUpgrade->oDBUpgrader->oTable = &$this->oaTable;

            $postscript = new OA_UpgradePostscript_2_5_67_RC8();
            $postscript->execute(array(&$mockUpgrade));

            $value = $this->oDbh->queryOne("SELECT nextval('$sequenceName')");

            $this->assertTrue($value > 2, "The current sequence value is $value.");
        }
    }
}
?>
