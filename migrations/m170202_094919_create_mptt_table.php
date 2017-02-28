<?php

use yii\db\Migration;

/**
 * Handles the creation for table `mptt_table`.
 */
class m170202_094919_create_mptt_table extends Migration
{
    /**
     * @inheritdoc
    DROP TABLE IF EXISTS `tbl_category`;
    CREATE TABLE `tbl_category` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `root` int(10) unsigned DEFAULT NULL,
      `lft` int(10) unsigned NOT NULL,
      `rgt` int(10) unsigned NOT NULL,
      `level` smallint(5) unsigned NOT NULL,
      `name` varchar(255) NOT NULL,
      `summary` varchar(255) NOT NULL,
      `seo_title` varchar(255) NOT NULL,
      `seo_keywords` varchar(255) NOT NULL,
      `seo_description` varchar(255) NOT NULL,
      `created_by` int(11) NOT NULL,
      `updated_by` int(11) NOT NULL,
      `created_at` int(11) NOT NULL,
      `updated_at` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `root` (`root`),
      KEY `lft` (`lft`),
      KEY `rgt` (`rgt`),
      KEY `level` (`level`)
    ) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8;
     */
    public function up()
    {
        $option='ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        $option='';
        $this->createTable('mptt', [
                'id' => $this->bigInteger()->unsigned()->notNull(),
                'root' => $this->bigInteger()->unsigned()->defaultExpression('NULL'),
                'lft' => $this->integer()->unsigned()->notNull(),
                'rgt' => $this->integer()->unsigned()->notNull(),
                'level' => $this->smallInteger()->unsigned()->notNull(),
                'parent' => $this->bigInteger()->unsigned()->defaultExpression('0'),
                'name' => $this->string()->notNull(),
                'value' => $this->string(),
                'data' => $this->text(),
                'type' => $this->string(10),
                'summary' => $this->string(),
                'seo_title' => $this->string(),
                'seo_keywords' => $this->string(),
                'seo_description' => $this->string(),
                'created_by' => $this->integer(),
                'updated_by' => $this->integer(),
                'created_at' => $this->integer(),
                'updated_at' => $this->integer(),
                'optimistic_lock' => $this->integer()->defaultValue(0),
                'PRIMARY KEY (`id`)',
                'KEY `root` (`root`)',
                'KEY `lft` (`lft`)',
                'KEY `rgt` (`rgt`)',
                'KEY `level` (`level`)',
            ], $option);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('mptt');
    }
}
